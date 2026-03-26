<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Vaimo\AepEventStreaming\Api\ConfigInterface;
use Vaimo\AepEventStreaming\Model\Order\SynchronisePublisher;

class AddOrderToAepSyncQueue implements ObserverInterface
{
    private ConfigInterface $config;
    private SynchronisePublisher $publisher;

    public function __construct(
        SynchronisePublisher $publisher,
        ConfigInterface $config
    ) {
        $this->publisher = $publisher;
        $this->config = $config;
    }

    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        if (!$this->config->isEnabled() || $order->getCustomerIsGuest()) {
            return;
        }

        $this->publisher->publish($order);
    }
}
