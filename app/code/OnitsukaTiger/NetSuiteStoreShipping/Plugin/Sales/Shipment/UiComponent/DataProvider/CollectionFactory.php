<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Plugin\Sales\Shipment\UiComponent\DataProvider;

use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory as ParentCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Grid\Collection as ShipmentGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Order\Grid\Collection as ShipmentOrderGridCollection;

class CollectionFactory
{
    /**
     * @param ParentCollectionFactory $subject
     * @param \Closure $proceed
     * @param $requestName
     * @return mixed
     */
    public function aroundGetReport(
        ParentCollectionFactory $subject,
        \Closure $proceed,
        $requestName
    ) {
        $collection = $proceed($requestName);
        if ($requestName == 'sales_order_shipment_grid_data_source') {
            if ($collection instanceof ShipmentGridCollection) {
                $collection->getSelect()->joinLeft(
                    [
                        'source' => 'inventory_shipment_source'
                    ],
                    'source.shipment_id = main_table.entity_id',
                    ['source_code']
                )->joinInner(
                    ['inventory_source' => 'inventory_source'],
                    'source.source_code = inventory_source.source_code',
                    ['name']
                );
                $collection->addFilterToMap('source_code', 'inventory_source.source_code');
            }
        }

        return $collection;
    }
}
