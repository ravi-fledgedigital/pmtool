<?php

namespace OnitsukaTigerCpss\Pos\Observer;

use Magento\Framework\Event\ObserverInterface;
use Cpss\Pos\Helper\CreateCsv;
use Cpss\Pos\Cron\UpdatePosData;
use Cpss\Pos\Logger\Logger;
use OnitsukaTigerCpss\Pos\Helper\HelperData;

class CpssCreditmemoAfter  extends \Cpss\Pos\Observer\CpssCreditmemoAfter
{
    protected $helper;
    public function __construct(CreateCsv $createCsv, Logger $logger, HelperData $helper)
    {
        $this->helper = $helper;
        parent::__construct($createCsv, $logger);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $creditmemo->getOrder();

        if (!$this->helper->isEnableModule($order->getStoreId()) || $order->getCustomerIsGuest()) {
            return $this;
        }
        return parent::execute($observer);
    }

}
