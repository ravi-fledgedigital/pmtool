<?php

namespace Seoulwebdesign\Toast\Observer;

use Magento\Framework\Event\Observer;
use Seoulwebdesign\Toast\Helper\Data;

class SendPaymentCaptureMessage implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * The constructor
     *
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->_helper = $helper;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /* @var $order \Magento\Sales\Model\Order */
        $order = $observer->getEvent()->getPayment()->getOrder();
        if ($this->_helper->getIsEnabled($order->getStoreId())) {
            $this->_helper->sendMessage(\Seoulwebdesign\Toast\Model\Message::PAYMENT_CAPTURED, [
                'order' => $order,'storeId' => $order->getStoreId()
            ], true);
        }
    }
}
