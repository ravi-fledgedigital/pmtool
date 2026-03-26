<?php

namespace OnitsukaTiger\Command\Console\Catalog\Product;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImageUpdateFromCsv extends Command
{
    const CSV_FILENAME = 'small_image.csv';


    /**
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\App\State $appState
     * @param string|null $name
     */
    public function __construct(
        private \Magento\Catalog\Model\ProductFactory $productFactory,
        private \Magento\Framework\App\ResourceConnection $resourceConnection,
        private \Magento\Framework\Filesystem\DirectoryList $directoryList,
        protected \Magento\Framework\App\State $appState,
        string $name = null
    ) {
        parent::__construct($name);
        $this->appState = $appState;
    }

    /**
     * Method configure
     */
    protected function configure()
    {
        $this->setName('OT:configurable-product-image-update-from-csv');
        $this->setDescription('Configurable Product Image Update From Csv');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $csvFilePath = $this->getVarFolderPath() . '/import_export/' . self::CSV_FILENAME;
        $data = file_get_contents($csvFilePath);
        $rows = explode(PHP_EOL, $data);
        $this->appState->setAreaCode('crontab');

        $this->output->writeln("------- Image Upload Start -------");

        // Product table
        $connection  = $this->resourceConnection->getConnection();
        $catalogProductEntityMediaGallery = $connection->getTableName('catalog_product_entity_media_gallery');
        $catalogProductEntityMediaGalleryValue = $connection->getTableName('catalog_product_entity_media_gallery_value');
        $catalogProductEntityMediaGalleryValueToEntity = $connection->getTableName('catalog_product_entity_media_gallery_value_to_entity');
        $catalogProductEntityVarchar = $connection->getTableName('catalog_product_entity_varchar');
        $updateProductCount = 0;

        $i = 0;
        foreach ($rows as $row) {
            if ($i == 0) {
                $i++;
                continue;
            }

            $i++;
            $rowData = str_getcsv($row);
            $sku = (isset($rowData[0]) && !empty($rowData[0])) ? trim($rowData[0]) : '';
            $this->output->writeln("===============================================================");
            $this->output->writeln("--------Product SKU: " . $sku . "--------");

            if (!empty($sku)) {
                $productId = $this->productFactory->create()->getIdBySku($sku);

                $productImageUrl = (isset($rowData[1]) && !empty($rowData[1])) ? trim($rowData[1]) : '';
                if (!empty($productImageUrl)) {
                    $this->output->writeln("--------Product ID: " . $productId . "--------");
                    $productImageUrl = $productImageUrl . '?qlt=100&wid=240&hei=300&bgc=255,255,255&resMode=bisharp';
                    $this->output->writeln("--------Image URL: " . $productImageUrl . "--------");
                    $cpemgColumnValues = [
                        'attribute_id' => '90',
                        'value' => $productImageUrl,
                        'media_type' => 'image',
                        'disabled' => '0'
                    ];

                    $connection->insert($catalogProductEntityMediaGallery, $cpemgColumnValues);
                    $cpemgLastInsertedId = $connection->lastInsertId($catalogProductEntityMediaGallery);
                    $this->output->writeln("-------- Catalog Product Entity Media Gallery Last Inserted ID: " . $cpemgLastInsertedId . "--------");

                    if ($cpemgLastInsertedId) {
                        $cpemgvColumnValues = [
                            'value_id' => $cpemgLastInsertedId,
                            'store_id' => '0',
                            'label' => null,
                            'position' => '1',
                            'disabled' => '0',
                            'record_id' => null,
                            'row_id' => $productId
                        ];

                        $connection->insert($catalogProductEntityMediaGalleryValue, $cpemgvColumnValues);

                        $cpemgvteColumnValues = [
                            'value_id' => $cpemgLastInsertedId,
                            'row_id' => $productId
                        ];

                        $connection->insert($catalogProductEntityMediaGalleryValueToEntity, $cpemgvteColumnValues);

                        $cpevUpdateQuery = "UPDATE `$catalogProductEntityVarchar` SET `value` = '$productImageUrl' WHERE `$catalogProductEntityVarchar`.`row_id` = $productId AND attribute_id = 88 AND store_id in (0,1,2,3,4,5)";
                        $connection->query($cpevUpdateQuery);
                        $updateProductCount++;
                    }
                }
            }

            $this->output->writeln("===============================================================");
        }

        $this->output->writeln("--------Total Product Count: " . count($rows) . "--------");
        $this->output->writeln("--------Updated Product Count: " . $updateProductCount . "--------");



        $this->output->writeln("------- Image Upload End -------");

        return Command::SUCCESS;
    }

    /**
     * Get var folder path
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getVarFolderPath()
    {
        return $this->directoryList->getPath('var');
    }
}
