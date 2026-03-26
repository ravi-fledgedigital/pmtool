<?php

namespace OnitsukaTiger\Shipment\Plugin\Sales\Shipment\UiComponent\DataProvider;

use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory as ParentCollectionFactory;

class CollectionFactory
{
    /**
     * @param ParentCollectionFactory $subject
     * @param $result
     * @param $requestName
     * @return mixed
     */
    public function afterGetReport(ParentCollectionFactory $subject, $result, $requestName)
    {
        $collection = $result;
        if ($requestName == 'sales_order_shipment_grid_data_source') {
                $collection->getSelect()->joinLeft('shipment_extension_attributes',
                    "shipment_extension_attributes.shipment_id = `main_table`. entity_id",
                    ['status']
                );
                $collection->addFilterToMap('status', 'shipment_extension_attributes.status');
        }

        return $collection;
    }
}
