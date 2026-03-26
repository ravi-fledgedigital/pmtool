<?php

namespace OnitsukaTiger\Command\Console\Catalog\Product;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImageUpdate extends Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @param \Magento\Framework\App\State $appState
     * @param string|null $name
     */
    public function __construct(
        private \Magento\Catalog\Model\ProductFactory $productFactory,
        private \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\State $appState,
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
        $this->setName('OT:configurable-product-image-update');
        $this->setDescription('Configurable Product Image Update');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->appState->setAreaCode('crontab');

        $this->output->writeln("------- Image Upload Start -------");

        $collection = $this->productFactory->create()->getCollection();
        $collection->addAttributeToSelect('google_shop_image_link');
        $collection->addFieldToFilter('type_id', ['eq' => 'configurable']);

        if ($collection && $collection->getSize() > 0) {
            $connection  = $this->resourceConnection->getConnection();
            $catalogProductEntityMediaGallery = $connection->getTableName('catalog_product_entity_media_gallery');
            $catalogProductEntityMediaGalleryValue = $connection->getTableName('catalog_product_entity_media_gallery_value');
            $catalogProductEntityMediaGalleryValueToEntity = $connection->getTableName('catalog_product_entity_media_gallery_value_to_entity');
            $catalogProductEntityVarchar = $connection->getTableName('catalog_product_entity_varchar');
            $updateProductCount = 0;
            foreach ($collection as $product) {
                $this->output->writeln("--------Product ID: " . $product->getId() . "--------");
                $productImageUrl = $product->getGoogleShopImageLink();
                if (!empty($productImageUrl)) {
                    $productImageUrl = $productImageUrl . '?qlt=100&wid=240&hei=300&bgc=255,255,255&resMode=bisharp';
                    $productId = $product->getId();
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

                        $cpevUpdateQuery = "UPDATE `$catalogProductEntityVarchar` SET `value` = '$productImageUrl' WHERE `$catalogProductEntityVarchar`.`row_id` = $productId AND attribute_id = 88 AND store_id in (0,1)";
                        $connection->query($cpevUpdateQuery);
                        $updateProductCount++;
                    }
                }
            }

            $this->output->writeln("--------Total Product Count: " . $collection->getSize() . "--------");
            $this->output->writeln("--------Updated Product Count: " . $updateProductCount . "--------");
        }

        $this->output->writeln("------- Image Upload End -------");

        return Command::SUCCESS;
    }
}
