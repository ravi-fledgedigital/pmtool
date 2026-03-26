<?php

namespace OnitsukaTigerCpss\Pos\Observer;

use Magento\Framework\Event\ObserverInterface;
use Cpss\Pos\Helper\CreateCsv;
use Cpss\Pos\Cron\UpdatePosData;
use Cpss\Pos\Logger\Logger;
use OnitsukaTigerCpss\Pos\Helper\HelperData;

class CpssCancelOrder extends \Cpss\Pos\Observer\CpssCancelOrder
{
    protected $helper;
    public function __construct(CreateCsv $createCsv, Logger $logger,HelperData $helper)
    {
        $this->helper = $helper;
        parent::__construct($createCsv, $logger, $helper);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->helper->isEnableModule()){
            return $this;
        }
        return parent::execute($observer);
    }

}
