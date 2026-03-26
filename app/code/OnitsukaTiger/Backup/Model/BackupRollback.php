<?php

namespace OnitsukaTiger\Backup\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Backup\Factory;
use Magento\Framework\Backup\Filesystem\Helper;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Setup\LoggerInterface;


/**
 * Override Class BackupRollback
 * @package OnitsukaTiger\Backup\Model
 */
class BackupRollback extends \Magento\Framework\Setup\BackupRollback
{
    const DIRECTORY_PRODUCT_IMAGE_CACHE = 'catalog/product/cache';

    /**
     * Filesystem Directory List
     *
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var File
     */
    private $file;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * Path to backup folder
     *
     * @var string
     */
    private $backupsDir;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Override BackupRollback constructor.
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface $log
     * @param DirectoryList $directoryList
     * @param File $file
     * @param Helper $fsHelper
     * @throws FileSystemException
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        LoggerInterface $log,
        DirectoryList $directoryList,
        File $file,
        Helper $fsHelper
    ) {
        parent::__construct($objectManager, $log, $directoryList, $file, $fsHelper);
        $this->objectManager = $objectManager;
        $this->log = $log;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->backupsDir = $this->directoryList->getPath(DirectoryList::VAR_DIR)
            . '/' . self::DEFAULT_BACKUP_DIRECTORY;
    }

    /**
     * Take backup for code base
     * @Override
     * @param int $time
     * @param string $type
     * @return string
     * @throws LocalizedException
     */
    public function codeBackup($time, $type = Factory::TYPE_FILESYSTEM)
    {
        /** @var \Magento\Framework\Backup\Filesystem $fsBackup */
        $fsBackup = $this->objectManager->create(\Magento\Framework\Backup\Filesystem::class);
        $fsBackup->setRootDir($this->directoryList->getRoot());
        if ($type === Factory::TYPE_FILESYSTEM) {
            $fsBackup->addIgnorePaths($this->getCodeBackupIgnorePaths());
            $granularType = 'Code';
            $fsBackup->setName('code');
        } elseif ($type === Factory::TYPE_MEDIA) {
            $fsBackup->addIgnorePaths($this->getMediaBackupIgnorePaths());
            $granularType = 'Media';
            $fsBackup->setName('media');
        } else {
            throw new LocalizedException(new Phrase("This backup type \'$type\' is not supported."));
        }
        if (!$this->file->isExists($this->backupsDir)) {
            $this->file->createDirectory($this->backupsDir);
        }
        $fsBackup->setBackupsDir($this->backupsDir);
        $fsBackup->setBackupExtension('tgz');
        $fsBackup->setTime($time);
        $this->log->log($granularType . ' backup is starting...');
        $fsBackup->create();
        $this->log->log(
            $granularType . ' backup filename: ' . $fsBackup->getBackupFilename()
            . ' (The archive can be uncompressed with 7-Zip on Windows systems)'
        );
        $this->log->log($granularType . ' backup path: ' . $fsBackup->getBackupPath());
        $this->log->logSuccess($granularType . ' backup completed successfully.');
        return $fsBackup->getBackupPath();
    }

    /**
     * @Override
     * @return array
     * @throws FileSystemException
     */
    private function getMediaBackupIgnorePaths()
    {
        // Add ignore paths /magento/pub/media/catalog/product/cache
        $ignorePaths[] = $this->directoryList->getPath(DirectoryList::MEDIA) . '/' . self::DIRECTORY_PRODUCT_IMAGE_CACHE;
        foreach (new \DirectoryIterator($this->directoryList->getRoot()) as $item) {
            if (!$item->isDot() && ($this->directoryList->getPath(DirectoryList::PUB) !== $item->getPathname())) {
                $ignorePaths[] = str_replace('\\', '/', $item->getPathname());
            }
        }
        foreach (new \DirectoryIterator($this->directoryList->getPath(DirectoryList::PUB)) as $item) {
            if (!$item->isDot() && ($this->directoryList->getPath(DirectoryList::MEDIA) !== $item->getPathname())) {
                $ignorePaths[] = str_replace('\\', '/', $item->getPathname());
            }
        }
        return $ignorePaths;
    }

    /**
     * @Override
     * @return array
     * @throws FileSystemException
     */
    private function getCodeBackupIgnorePaths()
    {
        return [
            $this->directoryList->getPath(DirectoryList::MEDIA),
            $this->directoryList->getPath(DirectoryList::STATIC_VIEW),
            $this->directoryList->getPath(DirectoryList::VAR_DIR),
            $this->directoryList->getRoot() . '/update',
            $this->directoryList->getRoot() . '/node_modules',
            $this->directoryList->getRoot() . '/.grunt',
            $this->directoryList->getRoot() . '/.idea',
            $this->directoryList->getRoot() . '/.svn',
            $this->directoryList->getRoot() . '/.git'
        ];
    }
}

