<?php

namespace OnitsukaTigerKorea\Sales\Observer;

use OnitsukaTiger\Sales\Helper\Data;

class CancelOrder extends \OnitsukaTiger\Sales\Observer\CancelOrder
{
    /**
     * @var Data
     */
    private $emailHelper;

    public function __construct(Data $emailHelper)
    {
        $this->emailHelper = $emailHelper;
        parent::__construct($emailHelper);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $observer->getEvent()->getOrder();
        $notSendCancelMail = $observer->getEvent()->getNotSendCancelMail();
        if (!$notSendCancelMail) {
            $this->emailHelper->sendCancellEmailTemplate($order);
        }
    }
}
