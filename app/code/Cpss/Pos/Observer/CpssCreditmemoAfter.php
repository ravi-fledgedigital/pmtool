<?php

namespace Cpss\Pos\Observer;

use Magento\Framework\Event\ObserverInterface;
use Cpss\Pos\Helper\CreateCsv;
use Cpss\Pos\Cron\UpdatePosData;
use Cpss\Pos\Logger\Logger;

class CpssCreditmemoAfter implements ObserverInterface
{

    protected $createCsv;
    protected $logger;

    public function __construct(
        CreateCsv $createCsv,
        Logger $logger
    ) {
        $this->createCsv = $createCsv;
        $this->logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $creditmemo = $observer->getEvent()->getCreditmemo();
            if ($creditmemo->getOrder()->getCustomerId()) {
                $suffixType = $this->createCsv->generateEcData($creditmemo, null, "creditmemo");
                $this->createCsv->generateEcItemsData(
                    $creditmemo,
                    $creditmemo->getOrderId(),
                    'creditmemo',
                    $suffixType
                );
                $this->createCsv->generateEcProductData($creditmemo->getOrder());
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return $this;
    }
}
