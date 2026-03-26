<?php

namespace OnitsukaTiger\PreOrders\Console\Command;

use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use OnitsukaTiger\Logger\Api\Logger;
use OnitsukaTiger\PreOrders\Helper\Data;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DisablePreOrder extends Command
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var _productloader
     */
    protected $_productloader;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var State
     */
    private $state;

    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var Action
     */
    protected $action;

    protected $output;

    protected $dateTime;

    const WEBSITEIDS = 'websiteids';

    protected $scopeConfig;

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param State $state
     * @param Data $dataHelper
     * @param Action $action
     * @param $name = null
     */
    public function __construct(
        ResourceConnection                              $resourceConnection,
        ProductFactory                                  $_productloader,
        StoreManagerInterface                           $storeManager,
        Logger                                          $logger,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        State                                           $state,
        Data                                            $dataHelper,
        Action                                          $action,
        DateTime                                        $dateTime,
        ScopeConfigInterface                            $scopeConfig,
        string                                          $name = null
    ) {
        parent::__construct($name);
        $this->resourceConnection = $resourceConnection;
        $this->_productloader = $_productloader;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->state = $state;
        $this->dataHelper = $dataHelper;
        $this->action = $action;
        $this->dateTime = $dateTime;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Method configure
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::WEBSITEIDS,
                null,
                InputOption::VALUE_REQUIRED,
                'Website Ids'
            )
        ];
        $this->setName("preorders:preorder-product-list-disable");
        $this->setDefinition($options);
        $this->setDescription(
            "Get product list Preorder enable AND End date check with current date and get yesterday data"
        );
        parent::configure();
    }

    /**
     * Method execute the disable pre order products
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $websiteIds = $input->getOption(self::WEBSITEIDS);
            $this->output = $output;
            $websiteIds = explode(',', $websiteIds);
            $storeIdsBasedOnWebsiteIds = [];
            foreach ($websiteIds as $websiteId) {
                $storeIdsOfWebsites = $this->storeManager->getWebsite(trim($websiteId))->getStores();
                $storeIdsBasedOnWebsiteIds += $storeIdsOfWebsites;
            }

            $storeIds = array_keys($this->storeManager->getStores(true));
            $websiteIds = array_keys($this->storeManager->getWebsites(true));
            unset($storeIds[1]);
            unset($websiteIds[1]);

            // set area code
            $this->state->setAreaCode('crontab');

            $connection = $this->resourceConnection->getConnection();

            $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/disable_pre_order.log");
            $logger = new \Zend_Log();
            $logger->addWriter($writer);

            if (!empty($storeIds)) {
                foreach ($storeIds as $storeId) {
                    if (array_key_exists($storeId, $storeIdsBasedOnWebsiteIds)) {
                        $storeTimezone = $this->scopeConfig->getValue('general/locale/timezone', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
                        $timeZone = new \DateTimeZone($storeTimezone);
                        $date = new \DateTime('now', $timeZone);
                        $date->modify('-1 day');
                        $yesterdayDate = $date->format('Y-m-d');
                        $logger->info("Store Id: " . $storeId);
                        $logger->info("Timezone: " . $storeTimezone);
                        $logger->info("Yesterday Date: " . $yesterdayDate);
                        $collection = $this->_productloader->create()->getCollection();
                        $collection->addAttributeToSelect(['sku', 'pre_order_status', 'end_date_preorder', 'store_id']);
                        $collection->addStoreFilter($storeId);
                        $collection->addAttributeToFilter('type_id', ['eq' => 'simple']);
                        $collection->addFieldToFilter('pre_order_status', ['eq' => 1]);
                        $collection->addFieldToFilter('end_date_preorder', ['lteq' => $yesterdayDate]);

                        if ($collection->getSize() > 0) {
                            $productDataByWebCode = [];
                            foreach ($collection as $product) {
                                $productId = $product->getId();
                                $sku = $product->getSku();
                                if ($storeId > 0) {
                                    $websiteId = (int)$this->storeManager
                                        ->getStore($storeId)
                                        ->getWebsiteId();
                                    $websiteCode = $this->storeManager
                                        ->getWebsite($websiteId)
                                        ->getCode();
                                    $productDataByWebCode[$sku][$storeId] = $websiteCode;
                                }
                                // disable pre order
                                $updatePreOrderAttributes = ['pre_order_status' => 0];
                                $this->updateAttribute($productId, 'pre_order_status', 0, $storeId);

                                $logger->info("Pre-Order disabled for $sku sku of store id - $storeId.");
                                $this->output->writeln("Pre-Order disabled for $sku sku of store id - $storeId.");
                            }

                            $wareHouseCode = [];
                            $sourceDataArr = [];

                            $wareHouseCode = $this->dataHelper->getWarehouseCode();

                            $issl = $connection->getTableName("inventory_source_stock_link");
                            $issc = $connection->getTableName("inventory_stock_sales_channel");

                            if (!empty($wareHouseCode)) {
                                foreach ($productDataByWebCode as $sku => $storeId) {
                                    foreach ($storeId as $websiteCode) {
                                        $select = "SELECT * FROM $issl AS issl
                                    LEFT JOIN $issc AS issc
                                    ON issl.stock_id = issc.stock_id
                                    WHERE issc.code = '$websiteCode'";

                                        $stockidData = $connection->fetchAll($select);
                                        foreach ($stockidData as $value) {
                                            if (in_array($value['source_code'], $wareHouseCode)) {
                                                $sourceDataArr[$sku]['source_code'] = $value["source_code"];
                                            }
                                        }
                                    }
                                }
                            } else {
                                $this->output->writeln("Please add the Warehouses from the module configuration");
                                exit();
                            }

                            $isi = $connection->getTableName("inventory_source_item");
                            if (!empty($sourceDataArr)) {
                                foreach ($sourceDataArr as $sku => $source) {
                                    $sourceCode = $source['source_code'];
                                    $selectInventoryBySku = "SELECT * FROM `$isi` WHERE `sku` = '$sku' AND `source_code` = '$sourceCode'";
                                    $inventorySku = $connection->fetchAll($selectInventoryBySku);

                                    if (!empty($inventorySku)) {
                                        foreach ($inventorySku as $sourceToUpdate) {
                                            $sourceItemId = $sourceToUpdate['source_item_id'];
                                            //$connection->query("UPDATE $isi SET `status` = '0' WHERE `source_item_id` = $sourceItemId");

                                            /*code for enable backorder when qty 0 and stock is enable start */
                                            $product = $this->productRepository->get($sku);
                                            $updateAttributes = [
                                                'use_config_backorders' => 1,
                                                'use_config_min_qty' => 1
                                            ];

                                            $product->setStockData($updateAttributes);
                                            $product->save();
                                            /*code for enable backorder when qty 0 and stock is enable end */

                                            $this->output->writeln("Stock status updated and disable Backorders");
                                        }
                                    } else {
                                        $this->output->writeln("warehouse is not assigned for $sku sku, so unable to update stock status and disable Backorders");
                                    }
                                }
                            } else {
                                $this->output->writeln("No warehouse is assigned for $sku sku of store id - $storeId.");
                            }
                        } else {
                            $this->output->writeln("-------------PreOrder Record not found for of store id - $storeId---------");
                        }
                    }
                }
            } else {
                $this->output->writeln("--------------Store Ids not found---------");
            }
        } catch (\Exception $e) {
            $this->output->writeln("------- An Error while updating the sources -------");
            $this->output->writeln("-------" . $e->getMessage());
            $this->output->writeln("------- END -------");
        }
        exit();
    }

    public function updateAttribute($productId, $attributeCode, $value, $storeId)
    {
        $product = $this->productRepository->getById($productId, false, $storeId);
        $product->setData($attributeCode, $value);
        $this->productRepository->save($product);
    }
}
