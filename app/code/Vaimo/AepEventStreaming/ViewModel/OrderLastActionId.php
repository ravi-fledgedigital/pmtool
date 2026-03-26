<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Api\Data\OrderInterface;

class OrderLastActionId implements ArgumentInterface
{
    public function getOrderExportStatus(OrderInterface $order): ?string
    {
        return $order->getExtensionAttributes()->getAepLastActionId();
    }
}
