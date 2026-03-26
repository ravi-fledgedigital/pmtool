<?php

namespace OnitsukaTiger\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;

class CancelOrder implements ObserverInterface
{
    /**
     * @var \OnitsukaTiger\Sales\Helper\Data
     */
    private $emailHelper;

    /**
     * CancelOrder constructor.
     * @param \Magento\Payment\Helper\Data $paymentHelper
     */
    public function __construct(
        \OnitsukaTiger\Sales\Helper\Data $emailHelper
    ) {
        $this->emailHelper = $emailHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $this->emailHelper->sendCancellEmailTemplate($order);
    }
}
