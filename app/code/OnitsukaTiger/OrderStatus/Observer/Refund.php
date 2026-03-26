<?php
namespace OnitsukaTiger\OrderStatus\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Payment;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;

class Refund implements ObserverInterface
{
    /**
     * @var OrderStatus
     */
    private $orderStatusModel;

    /**
     * Refund constructor.
     * @param OrderStatus $orderStatusModel
     */
    public function __construct(
        OrderStatus $orderStatusModel
    ) {
        $this->orderStatusModel = $orderStatusModel;
    }

    /**
     * Process the credit memo
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var Payment $payment */
        $payment = $observer->getEvent()->getPayment();
        $order = $payment->getOrder();

        // Override status comment from Payment class
        $statusHistories = $order->getStatusHistories();
        $lastStatusHistory = array_pop($statusHistories);
        $order->setStatusHistories($statusHistories);

        $this->orderStatusModel->setComment($lastStatusHistory->getComment())
            ->hasSave(false)
            ->setOrderStatus($order);
    }
}
