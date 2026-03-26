<?php

namespace OnitsukaTigerVn\Catalog\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Currency;

class ChangeCurrencyPosition implements ObserverInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    public function execute(Observer $observer)
    {
        $store = $this->storeManager->getStore();
        $storeId = $store->getId();

        if ($storeId == 8 || $storeId == 10) {
            $currencyOptions = $observer->getEvent()->getCurrencyOptions();
            $currencyOptions->setData('position', Currency::RIGHT);
        }

        return $this;
    }
}
