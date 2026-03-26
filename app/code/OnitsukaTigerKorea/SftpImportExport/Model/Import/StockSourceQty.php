<?php
//phpcs:ignoreFile
declare(strict_types=1);

namespace OnitsukaTigerKorea\SftpImportExport\Model\Import;

use Firebear\ImportExport\Model\Import\Context;
use Firebear\ImportExport\Model\ResourceModel\Import\Data as ImportData;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ImportExport\Helper\Data as DataHelper;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper as ResourceHelper;
use Magento\Indexer\Model\Indexer\StateFactory;
use Magento\InventoryImportExport\Model\Import\Serializer\Json as JsonHelper;
use Magento\InventoryImportExport\Model\Import\Validator\ValidatorInterface;
use Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku;
use OnitsukaTiger\Logger\Api\Logger as LoggerOnitsuka;
use OnitsukaTigerKorea\SftpImportExport\Helper\Data;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use OnitsukaTigerKorea\ConfigurableProduct\Helper\Data as HelperDataCatalog;
use OnitsukaTiger\Store\Model\Store;
use Magento\Catalog\Model\Product\Action;
use Magento\Framework\App\ResourceConnection;

/**
 * Class StockSourceQty
 * @package OnitsukaTiger\ImportExport\Model\Import
 */
class StockSourceQty extends \Firebear\ImportExportMsi\Model\Import\StockSourceQty {

    const FLAG_STOCK_SYNC_ATTRIBUTE = 'ignore_stock_sync';
    const FLAG_STOCK_SYNC_ATTRIBUTE_VALUE = 1;

    protected $storeid;
    protected $sourceCode;

    /**
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var GetSourceItemBySourceCodeAndSku
     */
    protected $getSourceItemBySourceCodeAndSku;

    /**
     * @var LoggerOnitsuka
     */
    protected $loggerOnitsuka;

    /**
     * @var \OnitsukaTiger\Store\Helper\Data
     */
    protected $helperStore;

    /**
     * @var HelperDataCatalog
     */
    protected $helperDataCatalog;

    /**
     * @var Action
     */
    protected $action;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * StockSourceQty constructor.
     * @param GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku
     * @param LoggerOnitsuka $loggerOnitsuka
     * @param CollectionFactory $productCollectionFactory
     * @param Data $helperData
     * @param ProductRepositoryInterface $productRepository
     * @param JsonHelper $jsonHelper
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param ResourceHelper $resourceHelper
     * @param DataHelper $dataHelper
     * @param ImportData $importData
     * @param ValidatorInterface $validator
     * @param ConsoleOutput $output
     * @param LoggerInterface $logger
     * @param array $commands
     * @param HelperDataCatalog $helperDataCatalog
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws ResourceConnection $resourceConnection
     */
    public function __construct(
        \OnitsukaTiger\Store\Helper\Data  $helperStore,
        GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        LoggerOnitsuka $loggerOnitsuka,
        CollectionFactory  $productCollectionFactory,
        Data $helperData,
        ProductRepositoryInterface $productRepository,
        Context $context,
        JsonHelper $jsonHelper,
        ValidatorInterface $validator,
        StateFactory $stateFactory,
        ProductFactory $productFactory,
        HelperDataCatalog $helperDataCatalog,
        Action $action,
        ResourceConnection $resourceConnection,
        array $commands = []
    ) {
        $this->helperStore = $helperStore;
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->loggerOnitsuka = $loggerOnitsuka;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->helperData = $helperData;
        $this->_productRepository = $productRepository;
        $this->helperDataCatalog = $helperDataCatalog;
        $this->action = $action;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $jsonHelper, $validator, $stateFactory, $productFactory, $commands);
    }

    /**
     * @param array $rowData
     * @param int $rowNum
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function validateRow(array $rowData, $rowNum): bool
    {
        if (isset($rowData[self::COL_STATUS]) &&
            $rowData[self::COL_STATUS] !== '' &&
            !in_array($rowData[self::COL_STATUS], [0, 1])
        ) {
            $this->addRowError('Invalid status value', $rowNum);
            return false;
        }

        if (isset($rowData[self::COL_SKU]) &&
            $rowData[self::COL_SKU] !== '' &&
            !is_null($rowData[self::COL_SKU]
            )
        ) {
            $product = $this->_productRepository->get($rowData[self::COL_SKU]);
            $ignoreStockSyncFlag = $product->getCustomAttribute(self::FLAG_STOCK_SYNC_ATTRIBUTE);
            if ((!is_null($ignoreStockSyncFlag) && ($ignoreStockSyncFlag->getValue() == self::FLAG_STOCK_SYNC_ATTRIBUTE_VALUE))
            ) {
                return false;
            }
        }

        return parent::validateRow($rowData, $rowNum);
    }

    protected function _saveValidatedBunches()
    {
        $source = $this->_getSource();
        $currentDataSize = 0;
        $bunchRows = [];
        $startNewBunch = false;
        $nextRowBackup = [];
        $maxDataSize = $this->_resourceHelper->getMaxDataSize();
        $bunchSize = $this->_importExportData->getBunchSize();

        $source->rewind();
        $this->_dataSourceModel->cleanBunches();
        $file = null;
        $jobId = null;
        if (isset($this->_parameters['file'])) {
            $file = $this->_parameters['file'];
        }
        if (isset($this->_parameters['job_id'])) {
            $jobId = $this->_parameters['job_id'];
        }

        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/kr_inventory_stock_update_job.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        while ($source->valid() || $bunchRows) {
            if ($startNewBunch || !$source->valid()) {
                $this->_dataSourceModel->saveBunches(
                    $this->getEntityTypeCode(),
                    $this->getBehavior(),
                    $jobId,
                    $file,
                    $bunchRows
                );
                $bunchRows = $nextRowBackup;
                $currentDataSize = strlen($this->jsonHelper->serialize($bunchRows));
                $startNewBunch = false;
                $nextRowBackup = [];
            }

            if ($source->valid()) {
                try {
                    $rowData = $source->current();
                    $this->_processedEntitiesCount++;
                } catch (\InvalidArgumentException $e) {
                    $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }
                $rowData = $this->customBunchesData($rowData);
                $rowData = $this->convertSkuWmsToSku($rowData);

                $logger->info('rowData print start');
                $logger->info(print_r($rowData,true));
                $logger->info('rowData print end');

                if(!$rowData['sku']) {
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }
                $this->updatePreOrderValue($rowData);
                $rowData = $this->checkAndUpdateStockByProductType($rowData);
                $this->_processedRowsCount++;
                if ($this->validateRow($rowData, $source->key())) {
                    $rowSize = strlen($this->jsonHelper->serialize($rowData));

                    $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;

                    if ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded) {
                        $startNewBunch = true;
                        $nextRowBackup = [$source->key() => $rowData];
                    } else {
                        $bunchRows[$source->key()] = $rowData;
                        $currentDataSize += $rowSize;
                    }
                }
                $source->next();
            }
        }

        foreach ($this->getErrorAggregator()->getAllErrors() as $error) {
            $this->addLogWriteln(
                __('%1 in row %2', $error->getErrorMessage(), $error->getRowNumber()),
                $this->output
            );
        }
        return $this;
    }

    /**
     * @param array $rowData
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     */
    protected function convertSkuWmsToSku(array $rowData): array
    {
        if(is_null($this->sourceCode) || $rowData['source_code'] != $this->sourceCode) {
            $this->storeid = $this->helperStore->getStoreIdFromSourceCode($rowData['source_code']);
            $this->sourceCode = $rowData['source_code'];
        }
         if($this->helperData->getGeneralConfig('enable', $this->storeid)) {
             foreach ($rowData as $importAttr => $sysAttr) {
                 if (
                     $importAttr == 'product_sku' ||
                     $importAttr == 'sku'
                 ) {
                     $sku_wms = $sysAttr;
                     $productCollection = $this->productCollectionFactory->create()
                         ->addStoreFilter($this->storeid)
                         ->addAttributeToSelect('sku')
                         ->addAttributeToFilter('sku_wms', ['eq' => $sku_wms])
                         ->load();
                     $rowData[$importAttr] = $productCollection->getFirstItem()->getSku();
                     if ($rowData[$importAttr]) {
                         $this->loggerOnitsuka->info(sprintf('sku_wms [%s] found sku [%s]', $sysAttr, $rowData[$importAttr]));
                     }else {
                         $this->loggerOnitsuka->info(sprintf('sku_wms [%s] doesn\'t find sku [%s]', $sysAttr, $rowData[$importAttr]));
                     }
                 }
             }
         }
        return $rowData;
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function checkAndUpdateStockByProductType(array $rowData): array
    {
        // disable restock from the product
        if(isset($rowData['sku']) && $rowData['sku'] !='' && isset($rowData['quantity']) && $rowData['quantity'] !=''){

            $sourceCode = '';
            if(isset($rowData['source_code']) && $rowData['source_code'] !=''){
                $sourceCode = $rowData['source_code'];
            }
            //$this->disableRestockFromProduct($rowData['sku'], $rowData['quantity'], $sourceCode);
        }

        if($this->helperData->getGeneralConfig('enable', $this->storeid)) {
            $updateSkuWms = true;
            foreach ($rowData as $importAttr => $sysAttr) {
                if (isset($rowData['product_type'])) {
                    if (
                        $importAttr == 'stock' ||
                        $importAttr == 'quantity'
                    ) {
                        if ($rowData['sku']) {
                            $this->loggerOnitsuka->info(sprintf('sku [%s] quantity [%s]', $rowData['sku'], $rowData[$importAttr]));
                        }
                        if ($rowData['product_type'] == 1 && $rowData[$importAttr] < 0) {
                            $rowData[$importAttr] = 0;
                        }
                        if ($rowData['product_type'] == 2) {
                            $currentQty = 0;
                            try {
                                $sourceItem = $this->getSourceItemBySourceCodeAndSku->execute($rowData['source_code'], $rowData['sku']);
                                $currentQty = $sourceItem->getQuantity();
                            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                                // not registered to the source yet
                                $this->loggerOnitsuka->info(sprintf('sku [%s] not registered yet in [%s]', $rowData['sku'], $rowData['source_code']));
                            }

                            // update quantity when product_type = 2
                            $rowData[$importAttr] += $currentQty;
                        }
                        // Quantity not accept negative number
                        if ($rowData[$importAttr] < 0) {
                            $rowData[$importAttr] = 0;
                        }
                        if ($rowData[$importAttr] == 0 && $updateSkuWms == true) {
                            $this->helperDataCatalog->updateSkuWms($rowData['sku'], $rowData['source_code']);
                            $updateSkuWms = false;
                        }
                    }
                }
            }
        }
        return $rowData;
    }

    private function updatePreOrderValue($rowData)
    {
        try {
            $product = $this->_productRepository->get($rowData['sku'], false, $this->storeid);
            if ($product->getIsPreOrderManagedFromReplenish()) {
                if (isset($rowData['preorder']) && $rowData['preorder']) {
                    $stockItem = $product->getExtensionAttributes()->getStockItem();
                    $qty = "-" . $rowData['stock'];
                    $stockData = [
                        'backorders' => 2,
                        'use_config_backorders' => 0,
                        'is_in_stock' => 1,
                        'min_qty' => $qty,
                        'use_config_min_qty' => 0,
                        //'manage_stock' => 1
                    ];
                    $stockItem->addData($stockData);
                    $this->_productRepository->save($product);
                    $this->loggerOnitsuka->info(sprintf('sku [%s] pre order data updated.', $rowData['sku']));
                } elseif (isset($rowData['preorder']) && !$rowData['preorder']) {
                    $stockItem = $product->getExtensionAttributes()->getStockItem();
                    $stockData = [
                        'use_config_backorders' => 1,
                        'use_config_min_qty' => 1
                    ];
                    $stockItem->addData($stockData);
                    $this->_productRepository->save($product);
                    $this->loggerOnitsuka->info(sprintf('sku [%s] pre order data reverted.', $rowData['sku']));
                }
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->loggerOnitsuka->info(sprintf('sku [%s] not found', $rowData['sku']));
        }

    }

    /**
     * @param array $rowData
     * @return array
     */
    public function disableRestockFromProduct($sku, $qty, $sourceCode)
    {
        $product = $this->_productRepository->get($sku);
        $storeIds = $product->getStoreIds();
        $updateAttributes = [];

        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/stock_update_job_restock.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        if($qty > 0){
            $updateAttributes['restock_notification_flag'] = "";
            $logger->info("$qty qty updated for $sourceCode source code and restock disable for $sku sku.");
        } elseif($qty == 0){
            $logger->info("$qty qty updated for $sourceCode source code and restock enable for $sku sku.");
            $updateAttributes['restock_notification_flag'] = "2";
        }

        if(!empty($storeIds) && $product && $product->getId()){
            foreach ($storeIds as $storeId) {
                if($storeId > 0){
                    $this->action->updateAttributes([$product->getId()], $updateAttributes, $storeId);
                }
            }
        }
    }
}
