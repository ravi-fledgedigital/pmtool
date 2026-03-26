<?php

namespace OnitsukaTigerIndo\Midtrans\Plugin\Model\Order;

use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;

class OrderRepository
{
    /**
     * Construct
     *
     * @param ObjectManagerInterface $objectManager
     */

    public function __construct(
        private ObjectManagerInterface $objectManager
    ) {
    }

    /**
     * Around Method of cancle order
     *
     * @param \Midtrans\Snap\Model\Order\OrderRepository $subject
     * @param mixed $proceed
     * @param Order $order
     * @param mixed $status
     * @param mixed $order_note
     * @return mixed
     */
    public function aroundCancelOrder(
        \Midtrans\Snap\Model\Order\OrderRepository $subject,
        $proceed,
        Order $order,
        $status,
        $order_note
    ) {
        $result = $proceed;
        if ($result) {
            $orderManagement = $this->objectManager->get(\Magento\Sales\Api\OrderManagementInterface::class)
                ->cancel($order->getId());
        }
        return $result;
    }
}
