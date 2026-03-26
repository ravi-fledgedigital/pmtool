<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UpdateDataShippingFromStore implements ObserverInterface
{
    const DEFAULT_VALUE = 1;

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $source = $observer->getEvent()->getSource();
        $request = $observer->getEvent()->getRequest();
        $requestData = $request->getPost()->toArray();
        $source->setData("is_shipping_from_store", $requestData['general']['is_shipping_from_store']);
        $source->setData("exclude_skus_from_inventory_sync", $requestData['general']['exclude_skus_from_inventory_sync']);
        $source->setData("show_acl", self::DEFAULT_VALUE);
    }
}
