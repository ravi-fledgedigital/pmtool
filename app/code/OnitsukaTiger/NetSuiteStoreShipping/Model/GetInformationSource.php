<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Model;

use Magento\Inventory\Model\ResourceModel\StockSourceLink\Collection;
use Magento\Inventory\Model\ResourceModel\StockSourceLink\CollectionFactory as StockSourceLinkCollection;
use Magento\Inventory\Model\SourceRepository;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;

class GetInformationSource
{
    const IS_ENABLE = 1;
    private StockSourceLinkCollection $stockCollection;
    private WebsiteCollectionFactory $websiteCollectionFactory;
    private SourceRepository $sourceRepository;

    /**
     * @param WebsiteCollectionFactory $websiteCollectionFactory
     * @param SourceRepository $sourceRepository
     * @param StockSourceLinkCollection $stockCollection
     */
    public function __construct(
        WebsiteCollectionFactory $websiteCollectionFactory,
        SourceRepository $sourceRepository,
        StockSourceLinkCollection $stockCollection
    ) {
        $this->websiteCollectionFactory = $websiteCollectionFactory;
        $this->sourceRepository = $sourceRepository;
        $this->stockCollection = $stockCollection;
    }

    /**
     * @param $condition
     * @return array
     */
    public function getShippingFromStoreData($condition = null): array
    {
        $shippingFromStoreData = [];
        $websiteCollection = $this->websiteCollectionFactory->create();
        foreach ($websiteCollection as $website) {
            $shippingFromStoreData[$website->getCode()] =  $this->getStockData($website, $condition);
        }
        return $shippingFromStoreData;
    }

    /**
     * @param $websiteCode
     * @return array
     */
    public function getStockData($websiteCode, $condition = null): array
    {
        $sourceData = [];
        $stockSourceLinkCollection = $this->stockCollection->create();
        $stockSourceLinkCollection->join(
            'inventory_stock_sales_channel',
            'main_table.stock_id = inventory_stock_sales_channel.stock_id',
        )->join(
            'inventory_source',
            'main_table.source_code = inventory_source.source_code',
        )->addFieldToFilter("code", $websiteCode->getCode());

        if ($condition == 'menu') {
            $stockSourceLinkCollection
                ->addFieldToFilter("inventory_source.enabled", self::IS_ENABLE)
                ->addFieldToFilter("inventory_source.is_shipping_from_store", self::IS_ENABLE);
        } else {
            $stockSourceLinkCollection->addFieldToFilter("inventory_source.show_acl", self::IS_ENABLE);
        }

        foreach ($stockSourceLinkCollection as $item) {
            $sourceData[$item->getSourceCode()] = $item->getFrontendName() ?: $item->getName();
        }

        return $sourceData;
    }

    /**
     * @param $condition
     * @return Collection
     */
    public function getSourceDuplicate($condition = null)
    {
        $stockSourceLinkCollection = $this->stockCollection->create();
        $stockSourceLinkCollection->join(
            'inventory_stock_sales_channel',
            'main_table.stock_id = inventory_stock_sales_channel.stock_id',
        )->join(
            'inventory_source',
            'main_table.source_code = inventory_source.source_code',
        );
        if ($condition == 'menu') {
            $stockSourceLinkCollection
                ->addFieldToFilter("inventory_source.enabled", self::IS_ENABLE)
                ->addFieldToFilter("inventory_source.is_shipping_from_store", self::IS_ENABLE);
        } else {
            $stockSourceLinkCollection->addFieldToFilter("inventory_source.show_acl", self::IS_ENABLE);
        }
        $stockSourceLinkCollection->getSelect()->group('main_table.source_code')->having('COUNT(main_table.source_code) > 1');

        return $stockSourceLinkCollection;
    }
}
