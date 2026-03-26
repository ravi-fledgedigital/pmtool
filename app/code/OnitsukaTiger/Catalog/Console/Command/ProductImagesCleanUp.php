<?php

namespace OnitsukaTiger\Catalog\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use OnitsukaTiger\Command\Console\Command;
use OnitsukaTiger\Logger\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class ProductImagesCleanUp extends Command
{
    const DELETE_MODE = 'delete';
    const LIST_MODE = 'list';
    const ALLOWED_FILE_TYPES = ['jpg', 'jpeg', 'png', 'gif'];
    const EXCLUDE_FOLDER = ['catalog/product/placeholder', 'catalog/product/cache'];

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var string
     */
    private $productImageDir;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * ProductImagesCleanUp constructor.
     * @param Logger $logger
     * @param ResourceConnection $resourceConnection
     * @param File $file
     * @param DirectoryList $directoryList
     * @param string|null $name
     * @throws FileSystemException
     */
    public function __construct(
        Logger $logger,
        ResourceConnection $resourceConnection,
        File $file,
        DirectoryList $directoryList,
        string $name = null
    ) {
        $this->connection = $resourceConnection->getConnection();
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->productImageDir = $this->getProductImageDir();
        parent::__construct($logger, $name);
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('catalog:product:images:cleanup')
            ->setDescription("Removes unused product images.")
            ->setDefinition([
                new InputOption(self::DELETE_MODE, "-d", InputOption::VALUE_NONE, "Delete Mode"),
                new InputOption(self::LIST_MODE, "-l", InputOption::VALUE_NONE, "List Mode")
            ]);
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws FileSystemException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $deleteMode = $input->getOption(self::DELETE_MODE);
        $listMode = $input->getOption(self::LIST_MODE);
        $localImages = $this->getProductImagesFromDirectory($this->productImageDir);
        $databaseImages = $this->getProductImagesFromDatabase();
        $deleteList = $this->createListToDelete($localImages, $databaseImages);

        try {
            if ($deleteMode) {
                $output->writeln("<info>Deleting Files</info>");
                $this->deleteImages($deleteList);
                $output->writeln("<info>All Done</info>");
            } elseif ($listMode) {
                $this->listDeleteList($deleteList);
            } else {
                $output->writeln("<comment>Test Mode Only - Nothing deleted</comment>");
                $output->writeln("<comment>Add -l option to shows a list of files to delete</comment>");
                $output->writeln("<comment>Add -d option to delete all unused images</comment>");
            }
            return Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Cli::RETURN_FAILURE;
        }
    }

    /**
     * @return array
     */
    private function getProductImagesFromDatabase()
    {
        $galleryImages = $this->getGalleryImages();
        $productImages = $this->getMediaImages();
        $databaseImages = array_unique(array_merge($galleryImages, $productImages));
        return $this->preparseValidDatabaseImages($databaseImages);
    }

    /**
     * @return array
     */
    private function getGalleryImages()
    {
        $table = $this->connection->getTableName('catalog_product_entity_media_gallery');
        $query = $this->connection->select()->from($table, ['value']);
        return array_unique($this->connection->fetchCol($query));
    }

    /**
     * @return array
     */
    private function getMediaImages()
    {
        $imagesFromVarCharTable = $this->getImagesFromEavTable('catalog_product_entity_varchar');
        $imagesFromTextTable = $this->getImagesFromEavTable('catalog_product_entity_text');
        return array_unique(array_merge($imagesFromVarCharTable, $imagesFromTextTable));
    }

    /**
     * @param $table
     * @return array
     */
    private function getImagesFromEavTable($table)
    {
        $tableName = $this->connection->getTableName($table);
        $query = $this->connection->select()
            ->from($tableName, ['value'])
            ->where('attribute_id IN (?)', $this->getImageAttributeIds());
        return $this->connection->fetchCol($query);
    }

    /**
     * @return array
     */
    private function getImageAttributeIds()
    {
        $eavTable = $this->connection->getTableName('eav_attribute');
        $entityTypeTable = $this->connection->getTableName('eav_entity_type');
        $query = $this->connection->select()
            ->from(
                ['eav' => $eavTable],
                ['attribute_id']
            )->joinLeft(
                ['entity_type' => $entityTypeTable],
                'eav.entity_type_id = entity_type.entity_type_id',
                []
            )->where(
                'entity_type.entity_type_code = (?)',
                'catalog_product'
            )->where('eav.frontend_input IN (?)', ['media_image', 'gallery']);

        return $this->connection->fetchCol($query);
    }

    /**
     * @param $images
     * @return array
     */
    private function preparseValidDatabaseImages($images)
    {
        foreach ($images as $key => $image) {
            if (empty($image)) {
                unset($images[$key]);
            } else {
                $fullPath = $this->file->getRealPath($this->productImageDir . $image);
                if (false === $fullPath) {
                    unset($images[$key]);
                } else {
                    $images[$key] = $fullPath;
                }
            }
        }
        return array_unique($images);
    }

    /**
     * @throws FileSystemException
     */
    private function getProductImagesFromDirectory($directory, &$results = [])
    {
        if ($directoryContents = $this->file->readDirectory($directory)) {
            foreach ($directoryContents as $path) {
                if (!is_dir($path)) {
                    if ($this->isAllowedFileExtension($path)) {
                        $results[] = $path;
                    }
                } elseif ($this->isAllowedFolder($path)) {
                    $this->getProductImagesFromDirectory($path, $results);
                }
            }
        }
        return $results;
    }

    /**
     * @throws FileSystemException
     */
    private function getProductImageDir()
    {
        $imagePath = DIRECTORY_SEPARATOR . 'catalog' . DIRECTORY_SEPARATOR . 'product';
        return $this->directoryList->getPath(DirectoryList::MEDIA) . $imagePath;
    }

    /**
     * @param $path
     * @return bool
     */
    private function isAllowedFileExtension($path)
    {
        $fileExtensionByPath = pathinfo($path, PATHINFO_EXTENSION);
        foreach (self::ALLOWED_FILE_TYPES as $fileExtension) {
            if ($fileExtension == $fileExtensionByPath) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $path
     * @return bool
     */
    private function isAllowedFolder(string $path)
    {
        foreach (self::EXCLUDE_FOLDER as $folderPath) {
            if (strpos($path, $folderPath)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $localImages
     * @param $productImages
     * @return array
     */
    private function createListToDelete($localImages, $productImages)
    {
        $productImagesFlip = array_flip($productImages);
        $deleteList = array();
        foreach ($localImages as $file) {
            if (!isset($productImagesFlip[$file])) {
                try {
                    if ($this->file->isWritable($file)) {
                        $deleteList[] = $file;
                    } else {
                        $this->output->writeln(
                            "<comment>Warning: File " . $file . " is not writable, skipping.</comment>"
                        );
                    }
                } catch (FileSystemException $e) {
                    $this->output->writeln("<comment>Warning: File " .$file. " is not writable, skipping.</comment>");
                }
            }
        }
        $this->output->writeln("<comment>Found " . count($deleteList) . " image files to be deleted</comment>");
        return $deleteList;
    }

    /**
     * @param $deleteList
     */
    private function deleteImages($deleteList)
    {
        foreach ($deleteList as $deleteFile) {
            try {
                $this->file->deleteFile($deleteFile);
            } catch (FileSystemException $e) {
                $this->output->writeln("<error>{$e->getMessage()}</error>");
            }
        }
    }

    /**
     * @param $deleteList
     */
    private function listDeleteList($deleteList)
    {
        if (!empty($deleteList)) {
            $this->output->writeln("<comment>Files marked for deletion:</comment>");
            foreach ($deleteList as $deleteFile) {
                $this->output->writeln($deleteFile);
            }
        }
    }
}
