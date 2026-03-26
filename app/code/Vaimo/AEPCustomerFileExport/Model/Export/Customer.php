<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPCustomerFileExport\Model\Export;

use Magento\Customer\Model\Customer as CustomerEntity;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\CustomerImportExport\Model\Export\Customer as OriginalExport;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\ImportExport\Model\Export\Factory;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\Store\Model\StoreManagerInterface;
use Vaimo\AEPFileExport\Model\ExportEntityInterface;

class Customer extends OriginalExport implements ExportEntityInterface
{
    public const LAST_RUN_FLAG_CODE = 'aep_customer_export_last_run';

    private Mapping $mapping;
    private FlagManager $flagManager;
    private DateTime $dateTime;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Factory $collectionFactory
     * @param CollectionByPagesIteratorFactory $resourceColFactory
     * @param TimezoneInterface $localeDate
     * @param Config $eavConfig
     * @param CollectionFactory $customerColFactory
     * @param Mapping $mapping
     * @param FlagManager $flagManager
     * @param DateTime $dateTime
     * @param mixed[] $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Factory $collectionFactory,
        CollectionByPagesIteratorFactory $resourceColFactory,
        TimezoneInterface $localeDate,
        Config $eavConfig,
        CollectionFactory $customerColFactory,
        Mapping $mapping,
        FlagManager $flagManager,
        DateTime $dateTime,
        protected \Vaimo\AepBase\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct(
            $scopeConfig,
            $storeManager,
            $collectionFactory,
            $resourceColFactory,
            $localeDate,
            $eavConfig,
            $customerColFactory,
            $data
        );
        $this->mapping = $mapping;
        $this->flagManager = $flagManager;
        $this->dateTime = $dateTime;
        $this->helper = $helper;
    }

    /**
     * @return string
     */
    public function export()
    {
        $this->_prepareEntityCollection($this->_getEntityCollection());
        $writer = $this->getWriter();

        // create export file
        $writer->setHeaderCols($this->_getHeaderColumns());
        $this->_exportCollectionByPages($this->_getEntityCollection());

        $this->flagManager->saveFlag(self::LAST_RUN_FLAG_CODE, $this->dateTime->date());

        return $writer->getContents();
    }

    /**
     * @param CustomerEntity $item
     * @return void
     */
    public function exportItem($item): void
    {
        $stores = ['Vietnam (English)' => 'VN', 'Vietnam (Vietnamese)' => 'VN','Singapore' => 'SG', 'Thailand (English)' => 'TH', 'Thailand (Thai)' => 'TH', 'Malaysia' => 'MY', 'Korea (Korean)' => 'KR', 'Indonesia (English)' => 'ID', 'Indonesia (Bahasa)' => 'ID'];
        $store = $stores[$item->getCreatedIn()] ?? null;
        if ($store === null) {
            return;
        }
        $sourceItem = $this->_addAttributeValuesToRow($item);
        $sourceItem['alert_stock_id'] = $item->getData('alert_stock_id');
        $result = [];

        foreach ($this->mapping->getMapping() as $colName => $mapItem) {
            switch ($mapItem['type']) {
                case Mapping::MAPPING_TYPE_SHIPPING_ATTRIBUTE:
                    if ($colName == 'Default_Shipping_Address_Street' && $item->getDefaultShippingAddress() && is_array($item->getDefaultShippingAddress()->getStreet())) {
                        $streets = implode(', ', $item->getDefaultShippingAddress()->getStreet());
                        $result[$colName] = $streets;
                    } else {
                        $result[$colName] = $sourceItem[Mapping::SHIPPING_ATTRIBUTES_PREFIX
                        . $mapItem['attribute']] ?? null;
                    }
                    break;
                case Mapping::MAPPING_TYPE_BILLING_ATTRIBUTE:
                    if ($colName == 'Default_Billing_Address_Street' && $item->getDefaultBillingAddress() && is_array($item->getDefaultBillingAddress()->getStreet())) {
                        $streets = implode(', ', $item->getDefaultBillingAddress()->getStreet());
                        $result[$colName] = $streets;
                    } else {
                        $result[$colName] = $sourceItem[Mapping::BILLING_ATTRIBUTES_PREFIX
                        . $mapItem['attribute']] ?? null;
                    }
                    break;
                case Mapping::MAPPING_TYPE_ATTRIBUTE:
                    $result[$colName] = $sourceItem[$mapItem['attribute']] ?? null;
                    break;
            }

            // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
            if (!empty($mapItem['data_modification_callback'])) {
                $callbackName = $mapItem['data_modification_callback'];
                $result[$colName] = \method_exists($this->mapping, $callbackName)
                    ? $this->mapping->$callbackName($sourceItem)
                    : null;
            }
        }
        $result['wishlist_products'] = $this->getWishlistItemArray($item->getId());
        $result['wishlist_modified_date'] = $item->getData('wishlist_modified_date');
        $result['cart_abandoned_products'] = $this->getAbandoneedCardItemArray($item->getId());
        $result['cart_modified_date'] = $this->getCartModifiedDate($item);

        /*if (!empty($store)) {
            $result['Customer_Base_Country'] = $store;
        }*/

        $this->getWriter()->writeRow($result);
        $this->_processedRowsCount++;
    }

    /**
     * @param $customerId
     * @return string
     */
    private function getWishlistItemArray($customerId)
    {
        $sku = '';
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $wishlistFactory = $objectManager->create(\Magento\Wishlist\Model\WishlistFactory::class);
        $wishlist = $wishlistFactory->create()->loadByCustomerId($customerId);
        if ($wishlist && $wishlist->getId()) {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/AEPCustomerExport.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('==========================AEP Customer Export Start============================');
            $logger->info('Wishlist ID: ' . $wishlist->getId());
            $logger->info('==========================AEP Customer Export End============================');
            $wishlistItem = $wishlist->getItemCollection();

            $wishlistItem->getSelect()->joinLeft(
                ['catalog_product_entity' => $wishlistItem->getConnection()->getTableName('catalog_product_entity')],
                'main_table.product_id = catalog_product_entity.entity_id',
                ['sku' => 'catalog_product_entity.sku']
            );

            if (count($wishlistItem) > 0) {
                try {
                    $skus = [];
                    foreach ($wishlistItem as $wItem) {
                        $skus[] = $wItem->getSku();//$wishlistItem->getColumnValues('sku');
                    }
                    $sku = implode(', ', $skus);
                } catch (\Exception $e) {
                    /*$logger->critical($e->getMessage());*/
                }

            }
        }

        return $sku;
    }

    /**
     * @param $customerId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAbandoneedCardItemArray($customerId)
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $quoteFactory = $objectManager->create(\Magento\Reports\Model\ResourceModel\Quote\CollectionFactory::class);
        $collection = $quoteFactory->create();
        $collection->prepareForAbandonedReport([$storeId]);
        $collection->addFieldToFilter('customer_id', ['eq' => $customerId])->load();
        $skuArray = [];
        if ($collection->getSize() > 0) {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            foreach ($collection as $quote) {
                try {
                    $itemCollection = $quote->getAllItems();
                    if (count($itemCollection) > 0) {
                        foreach ($itemCollection as $quoteItem) {
                            $itemSkus[] = $quoteItem->getSku();
                        }
                        $skuArray[] = implode(', ', $itemSkus);
                    }
                } catch (\Exception $e) {
                    $logger->critical($e->getMessage());
                }
            }
        }

        $sku = '';
        if (!empty($skuArray)) {
            $sku = implode(', ', $skuArray);
        }

        return $sku;
    }

    private function getCartModifiedDate($customer)
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $quoteFactory = $objectManager->create(\Magento\Quote\Model\QuoteFactory::class);
        $quote = $quoteFactory->create()->loadByCustomer($customer);
        if ($quote && $quote->getId() && $quote->getStoreId() == $storeId) {
            return $quote->getUpdatedAt();
        }

        return null;
    }

    protected function _prepareEntityCollection(AbstractCollection $collection): AbstractCollection
    {
        $this->filterEntityCollection($collection);
        $this->_addAttributesToCollection($collection);
        $this->applyMappingCallbacksToCollection($collection);
        $collection->getSelect()->group('e.entity_id');
        $configWebsiteIds = $this->helper->getExcludeWebsiteCustomer();
        $websiteIds = [];
        if (!empty($configWebsiteIds)) {
            $websiteIds = explode(',', $configWebsiteIds);
        }
        if (!empty($websiteIds)) {
            $collection->addAttributeToFilter("website_id", ["nin" => $websiteIds]);
        }
        /*$collection->addAttributeToFilter("website_id", ["eq" => 3]);*/

        $this->applyDeltaFilterAbstractCollection($collection);
        count($collection);
        return $collection;
    }

    private function applyMappingCallbacksToCollection(AbstractCollection $collection): void
    {
        foreach ($this->mapping->getMapping() as $item) {
            switch ($item['type']) {
                case Mapping::MAPPING_TYPE_SHIPPING_ATTRIBUTE:
                    $collection->joinAttribute(
                        Mapping::SHIPPING_ATTRIBUTES_PREFIX . $item['attribute'],
                        'customer_address/' . $item['attribute'],
                        'default_shipping',
                        null,
                        'left'
                    );
                    break;
                case Mapping::MAPPING_TYPE_BILLING_ATTRIBUTE:
                    $collection->joinAttribute(
                        Mapping::BILLING_ATTRIBUTES_PREFIX . $item['attribute'],
                        'customer_address/' . $item['attribute'],
                        'default_billing',
                        null,
                        'left'
                    );
                    break;
            }

            // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
            if (
                !empty($item['prepare_collection_callback'])
                && \method_exists($this->mapping, $item['prepare_collection_callback'])
            ) {
                $callback = $item['prepare_collection_callback'];
                $this->mapping->$callback($collection);
            }
        }
    }

    private function applyDeltaFilterAbstractCollection(AbstractCollection $collection): void
    {
        $lastRunDate = $this->flagManager->getFlagData(self::LAST_RUN_FLAG_CODE);
        if (empty($lastRunDate)) {
            return;
        }

        $this->mapping->joinSalesOrderTable($collection);
        $collection->getSelect()->columns(['last_order_updated_at' => 'sales_order.updated_at']);

        $this->mapping->joinCreditMemoTable($collection);
        $collection->getSelect()->columns(['last_return_updated_at' => 'sales_creditmemo.updated_at']);

        $this->joinWishlistTable($collection);

        $collection->getSelect()->where(
            'e.updated_at >= ? OR sales_order.updated_at >= ? OR sales_creditmemo.updated_at >= ?',
            [$lastRunDate]
        );
    }

    private function joinWishlistTable($collection)
    {
        $collection->getSelect()->joinLeft(
            ['wishlist' => $collection->getConnection()->getTableName('wishlist')],
            'e.entity_id = wishlist.customer_id',
            ['wishlist_modified_date' => 'wishlist.updated_at']
        );
    }

    /**
     * @return string[]
     */
    protected function _getHeaderColumns(): array
    {
        $mappingFields = \array_keys($this->mapping->getMapping());
        $mapField = array_diff($mappingFields, $this->getSkipCustomerAttributeArray());

        $mapField = array_merge($mapField, $this->addExtraCustomerExportField());

        return $mapField;
    }

    /**
     * @return string[]
     */
    private function getSkipCustomerAttributeArray()
    {
        return ['Marketing Email Consent','SMS Consent','StoreSignup','Modified_Date'];
    }

    /**
     * @return string[]
     */
    private function addExtraCustomerExportField()
    {
        return ['wishlist_products', 'wishlist_modified_date', 'cart_abandoned_products', 'cart_modified_date'];
    }

    /**
     * @return string[]
     */
    protected function _getExportAttributeCodes(): array
    {
        if ($this->_attributeCodes !== null) {
            return $this->_attributeCodes;
        }

        foreach ($this->mapping->getMapping() as $item) {
            switch ($item['type']) {
                case Mapping::MAPPING_TYPE_ATTRIBUTE:
                    $this->_attributeCodes[] = $item['attribute'];
                    break;
                case Mapping::MAPPING_TYPE_SHIPPING_ATTRIBUTE:
                    $this->_attributeCodes[] = Mapping::SHIPPING_ATTRIBUTES_PREFIX . $item['attribute'];
                    break;
                case Mapping::MAPPING_TYPE_BILLING_ATTRIBUTE:
                    $this->_attributeCodes[] = Mapping::BILLING_ATTRIBUTES_PREFIX . $item['attribute'];
                    break;
            }
        }

        return $this->_attributeCodes;
    }
}
