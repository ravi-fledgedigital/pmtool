<?php

namespace OnitsukaTigerIndo\Inventory\Console;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\State;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateProductInventorySourceStatus extends Command
{
    /**
     * @var State
     */
    protected $appState;

    private $output;

    /**
     * @param CreateCsv $createCsv
     * @param State $appState
     * @param string|null $name
     */
    public function __construct(
        State $appState,
        private CollectionFactory $productCollectionFactory,
        private GetSalableQuantityDataBySku $salableQtyData,
        private \Magento\Framework\App\ResourceConnection $resourceConnection,
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
        $this->setName('OT:invetory-status-update-indo');
        $this->setDescription('Change inventory status for 0 qty source');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->appState->setAreaCode('crontab');

        $this->output->writeln("------- Inventory update start -------");
        $productCollection = $this->updateProductStockStatus();
        $this->output->writeln("------- Inventory update end -------");

        return Command::SUCCESS;
    }

    /**
     * Get product collection of indo product.
     *
     * @return void
     * @throws \Zend_Log_Exception
     */
    private function updateProductStockStatus()
    {
        $collection = $this->productCollectionFactory->create();

        // Basic filters
        $collection->addAttributeToSelect(['sku', 'name'])
            ->addWebsiteFilter(5)
            ->addAttributeToFilter(
                'status',
                \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
            )
            ->addAttributeToFilter('type_id', 'simple');

        $tableName = $this->resourceConnection->getTableName('inventory_source_item'); // Get table name with prefix
        $skus = $collection->getColumnValues('sku');
        $skuArray = array_map(function ($value) {
            return '"' . $value . '"';
        }, $skus);
        $skuList = implode(", ", $skuArray);

        $query = "SELECT * FROM `" . $tableName . "` WHERE `sku` in($skuList) AND source_code = 'id_wh_default' AND quantity = 0 AND status = 1";
        $connection = $this->resourceConnection->getConnection();
        $results = $connection->fetchAll($query);

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/updateInventoryStockStatus.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Select Query: ' . $query);
        $logger->info('Query Result: ' . print_r($results, true));

        $updateQuery = "UPDATE " . $tableName . " SET `status` = '0' WHERE `sku` in($skuList) AND source_code = 'id_wh_default' AND quantity = 0 AND status = 1";
        $connection->query($updateQuery);
        $logger->info('Update Query: ' . $updateQuery);

    }
}
