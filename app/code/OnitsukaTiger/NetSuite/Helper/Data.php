<?php

namespace OnitsukaTiger\NetSuite\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\Website;
use Magento\InventoryCatalogAdminUi\Model\GetSourceItemsDataBySku;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\Store\Api\StoreWebsiteRelationInterface;
use Magento\Catalog\Model\Product\Action;
use OnitsukaTiger\NetSuite\Model\SourceMapping;
use Magento\Framework\Api\SearchCriteriaBuilder;
use OnitsukaTiger\Logger\Api\Logger;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;

/**
 * Class Create Data
 */
class Data extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var array
     */
    protected $productIdsArr;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var Website
     */
    protected $website;

    /**
     * @var GetSourceItemsDataBySku
     */
    protected $getSourceItemsDataBySku;

    /**
     * @var SourceRepositoryInterface
     */
    protected $sourceRepositoryInterface;

    /**
     * @var SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @var GetStockSourceLinksInterface
     */
    protected $getStockSourceLinksInterface;

    /**
     * @var StoreWebsiteRelationInterface
     */
    protected $storeWebsiteRelationInterface;

    /**
     * @var Action
     */
    protected $action;

    /**
     * @var SourceMapping
     */
    protected $sourceMapping;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        ProductRepositoryInterface $productRepository,
        ResourceConnection $resourceConnection,
        Website $website,
        GetSourceItemsDataBySku $getSourceItemsDataBySku,
        SourceRepositoryInterface $sourceRepositoryInterface,
        SortOrderBuilder $sortOrderBuilder,
        GetStockSourceLinksInterface $getStockSourceLinksInterface,
        StoreWebsiteRelationInterface $storeWebsiteRelationInterface,
        Action $action,
        SourceMapping $sourceMapping,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Logger $logger,
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->productRepository = $productRepository;
        $this->resourceConnection = $resourceConnection;
        $this->website = $website;
        $this->getSourceItemsDataBySku = $getSourceItemsDataBySku;
        $this->sourceRepositoryInterface = $sourceRepositoryInterface;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->getStockSourceLinksInterface = $getStockSourceLinksInterface;
        $this->storeWebsiteRelationInterface = $storeWebsiteRelationInterface;
        $this->action = $action;
        $this->sourceMapping = $sourceMapping;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    /**
     * Disable Restock item for product
     *
     * @param string $sku
     * @param int $stock
     */
    public function disableRestockFlag($sku, $stockId, $stock)
    {
        $sourceCode = $this->sourceMapping->getMagentoLocation($stockId);

        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/stock_update_netsuite_api.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $product = $this->productRepository->get($sku);
        $sku = $product->getSku();
        $websiteCode = '';
        $storeIds = [];

        $connection = $this->resourceConnection->getConnection();

        $issl = $connection->getTableName('inventory_source_stock_link');
        $issc = $connection->getTableName('inventory_stock_sales_channel');

        $select = "SELECT * FROM $issl AS issl
            LEFT JOIN $issc AS issc
            ON issl.stock_id = issc.stock_id
            WHERE issl.source_code = '$sourceCode' ";

        $stockCollection = $connection->fetchAll($select);
        $stockId = '';
        $websiteCode  = '';

        if(!empty($stockCollection) && isset($stockCollection[0]) && isset($stockCollection[0]['code']) && isset($stockCollection[0]['stock_id'])){
            $websiteCode = $stockCollection[0]['code'];
            $stockId = $stockCollection[0]['stock_id'];
            if($websiteCode){
                $websiteData = $this->website->load($websiteCode, 'code');
                $websiteId = $websiteData->getWebsiteId();
                if($websiteId){
                    $storeIds = $this->storeWebsiteRelationInterface->getStoreByWebsiteId($websiteId);
                }
            }
        }

        try {
            if($stock > 0){
                if(!empty($storeIds)){
                    $updateAttributes['restock_notification_flag'] = "";
                    foreach ($storeIds as $storeId) {
                        if($storeId > 0 ){
                            $logger->info("$stock qty updated for $sourceCode source and restock disabled for $sku sku.");
                            $this->action->updateAttributes([$product->getId()], $updateAttributes, $storeId);
                        }
                    }
                }
            }elseif($stock == 0){
                $sourceItems = $this->getSourceItemsDataBySku->execute($sku);

                $sourceItemsArray = [];
                foreach ($sourceItems as $itmes) {
                    $sourceItemsArray[$itmes['source_code']] = $itmes;
                }

                $sortOrder = $this->sortOrderBuilder->setField(StockSourceLinkInterface::PRIORITY)
                    ->setAscendingDirection()
                    ->create();
                $searchCriteria = $this->searchCriteriaBuilder->addFilter(StockSourceLinkInterface::STOCK_ID, $stockId)
                    ->addSortOrder($sortOrder)
                    ->create();

                $searchResult = $this->getStockSourceLinksInterface->execute($searchCriteria);

                if ($searchResult->getTotalCount() === 0) {
                    return [];
                }

                $assignedSourcesData = [];
                foreach ($searchResult->getItems() as $link) {
                    $source = $this->sourceRepositoryInterface->get($link->getSourceCode());
                    $assignedSourcesData[] = $source->getSourceCode();
                }

                $totalCount = 0;
                $noQtysourceCount = 0;
                $productToUpdate = [];

                foreach ($sourceItemsArray as $key => $sourceItems) {
                    if(in_array($key, $assignedSourcesData)){
                        $totalCount++;
                        if($sourceItems['status']  == 0){
                            $noQtysourceCount++;
                            $productToUpdate[$product->getId()] = $noQtysourceCount;
                        }
                    }
                }

                if($totalCount == $noQtysourceCount && $productToUpdate[$product->getId()] == $totalCount){
                    if(!empty($storeIds)){
                        $updateAttributes['restock_notification_flag'] = "2";

                        foreach ($storeIds as $storeId) {
                            if($storeId > 0){
                                $logger->info("$stock qty updated for $sourceCode source, $storeId store and restock disabled for $sku sku.");
                                $this->action->updateAttributes([$product->getId()], $updateAttributes, $storeId);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $logger->info('-----disable/enable restock flag Exception-----'.$e->getMessage());
        }
    }
}
