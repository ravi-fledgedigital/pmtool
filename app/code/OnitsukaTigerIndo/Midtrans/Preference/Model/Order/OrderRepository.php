<?php

namespace OnitsukaTigerIndo\Midtrans\Preference\Model\Order;

use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;

class OrderRepository extends \Midtrans\Snap\Model\Order\OrderRepository
{
    /*public function __construct(
        private ObjectManagerInterface $objectManager
    ) {
    }

    public function aroundCancelOrder(
        \Midtrans\Snap\Model\Order\OrderRepository $subject,
        $proceed,
        Order $order,
        $status,
        $order_note
    ) {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/midtrans/orderCancel.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $result = $proceed($order, $status, $order_note);
        if ($result) {
        $logger->info('Order Cancel Start Plugin');
        $this->objectManager->get(\Magento\Sales\Api\OrderManagementInterface::class)->cancel($order->getId());
        $logger->info('Order Cancel End Plugin');
        }
        return $result;
    }*/

    /**
     * Do cancel order, and set status, state, also comment status history
     *
     * @param Order $order
     * @param $status
     * @param $order_note
     * @return Order
     * @throws Exception
     */
    public function cancelOrder(Order $order, $status, $order_note)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/midtrans/orderCancel.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info('Order Cancel Start Plugin');
        $logger->info('Order Note: ' . $order_note);
        $logger->info('Order Status: ' . $status);
        /*$order->setState($status);
        $order->setStatus($status);*/
        //$order->addStatusToHistory($status, $order_note, false);
        $order->cancel();
        $this->saveOrder($order);
        $logger->info('Order Cancel End Plugin');
        return $order;
    }
}
