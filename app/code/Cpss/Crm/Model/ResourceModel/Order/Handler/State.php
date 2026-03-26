<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Cpss\Crm\Model\ResourceModel\Order\Handler;

use Magento\Sales\Model\Order;

/**
 * Checking order status and adjusting order status before saving
 */
class State extends \Magento\Sales\Model\ResourceModel\Order\Handler\State
{
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
                && !($order->canCreditmemo() || $this->isRefundedAll($order))
                && !$order->canShip()
                && $order->getIsNotVirtual()
            ) {
                $order->setState(Order::STATE_CLOSED)
                    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_CLOSED));
            } elseif ($currentState === Order::STATE_PROCESSING && !$order->canShip()) {
                $order->setState(Order::STATE_COMPLETE)
                    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_COMPLETE));
            }
        }
        return $this;
    }

    public function isRefundedAll($order)
    {
        $isRefundedAll = true;

        foreach ($order->getAllItems() as $item) {
            if ($item->getProductType() == 'simple') {
                continue;
            }

            if ($item->getQtyInvoiced() > $item->getQtyRefunded()) {
                $isRefundedAll = false;
                break;
            }
        }

        if (!$isRefundedAll) {
            return true;
        }

        return $isRefundedAll;
    }
}
