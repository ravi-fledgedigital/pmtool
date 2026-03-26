<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Vaimo\AepEventStreaming\Api\ConfigInterface;
use Vaimo\AepEventStreaming\Api\Data\IngestRecordInterface;

class OrderRepositoryPlugin
{
    private ConfigInterface $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order): OrderInterface
    {
        if (!$this->config->isEnabled()) {
            return $order;
        }

        $order->getExtensionAttributes()->setAepLastActionId(
            $order->getData(IngestRecordInterface::ATTRIBUTE_CODE)
        );

        return $order;
    }
}
