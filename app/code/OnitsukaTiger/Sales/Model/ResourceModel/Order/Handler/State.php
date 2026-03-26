<?php

namespace OnitsukaTiger\Sales\Model\ResourceModel\Order\Handler;

use Magento\Sales\Model\Order;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;

/**
 * Class State
 */
class State extends \Magento\Sales\Model\ResourceModel\Order\Handler\State
{

    /**
     * @var OrderStatus
     */
    protected $orderStatus;

    /**
     * State constructor.
     * @param OrderStatus $orderStatus
     */
    public function __construct(
        OrderStatus $orderStatus
    ) {
        $this->orderStatus = $orderStatus;
    }

    /**
     * Check order status and adjust the status before save
     *
     * @param Order $order
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function check(Order $order)
    {
        $currentState = $order->getState();
        if ($currentState == Order::STATE_NEW && $order->getIsInProcess()) {
            $order->setState(Order::STATE_PROCESSING)
                ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));
            $currentState = Order::STATE_PROCESSING;
        }

        if (!$order->isCanceled() && !$order->canUnhold() && !$order->canInvoice()) {
            if (in_array($currentState, [Order::STATE_PROCESSING, Order::STATE_COMPLETE])
                && !$order->canCreditmemo()
                && !$order->canShip()
                && $this->orderStatus->isAllowedCloseOrder($order)
            ) {
                $order->setState(Order::STATE_CLOSED)
                    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_CLOSED));
            }
        }
        return $this;
    }
}
