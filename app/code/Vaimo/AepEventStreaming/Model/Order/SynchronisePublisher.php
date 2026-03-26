<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model\Order;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Vaimo\AepEventStreaming\Api\ConfigInterface;

class SynchronisePublisher
{
    public const TOPIC_NAME = 'aep.order.synchronise';

    private PublisherInterface $publisher;
    private ConfigInterface $config;

    public function __construct(
        PublisherInterface $publisher,
        ConfigInterface $config
    ) {
        $this->publisher = $publisher;
        $this->config = $config;
    }

    public function publish(OrderInterface $order): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $this->publisher->publish(self::TOPIC_NAME, $order);
    }
}
