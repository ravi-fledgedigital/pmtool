<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\OrderStatus\Model\SourceDeduction;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Sales\Model\Order\Item as OrderItem;

class CancelOrderItem
{
    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * CancelOrderItem constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        $this->eventManager = $context->getEventDispatcher();
    }

    /**
     * @param OrderItem $orderItem
     * @return void
     */
    public function execute($orderItem): void
    {
        // When the payment status is "pending payment", the invoice is still generated
        // Because Magento can't cancel order Item which had invoice. So need reset qty invoiced
        $orderItem->setQtyInvoiced(0);
        $this->eventManager->dispatch('sales_order_item_cancel', ['item' => $orderItem]);
    }
}
