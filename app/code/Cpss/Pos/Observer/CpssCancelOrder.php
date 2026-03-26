<?php

namespace Cpss\Pos\Observer;

use Cpss\Pos\Helper\CreateCsv;
use Cpss\Pos\Logger\Logger;
use Magento\Framework\Event\ObserverInterface;

class CpssCancelOrder implements ObserverInterface
{
    protected $createCsv;
    protected $logger;

    /**
     * @var \OnitsukaTigerCpss\Pos\Helper\HelperData
     */
    protected $helper;

    public function __construct(
        CreateCsv $createCsv,
        Logger $logger,
        \OnitsukaTigerCpss\Pos\Helper\HelperData $helper
    ) {
        $this->createCsv = $createCsv;
        $this->logger = $logger;
        $this->helper = $helper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$this->helper->isEnableModule($order->getStoreId()) || $order->getCustomerIsGuest()) {
            return $this;
        }

        try {
            $suffixType = $this->createCsv->generateEcData($order);
            $this->createCsv->generateEcItemsData(
                $order,
                $order->getIncrementId(),
                null,
                $suffixType
            );
            $this->createCsv->generateEcProductData($order);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return $this;
    }
}
