<?php

namespace Seoulwebdesign\Toast\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Seoulwebdesign\Toast\Helper\Data;
use Seoulwebdesign\Toast\Model\Message as ToastMessage;

class SendOrderRefundMessage implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * SendOrderRefundMessage constructor.
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
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment  = $observer->getEvent()->getPayment();
        $order = $payment->getOrder();
        if ($this->_helper->getIsEnabled($order->getStoreId())) {
            /** @var \Magento\Sales\Model\Order\Invoice $invoice */
            $invoice  = $observer->getEvent()->getInvoice();
            $this->_helper->sendMessage(ToastMessage::ORDER_REFUNDED, [
                'order' => $order,'storeId' => $order->getStoreId()
            ], true);
        }
    }
}
