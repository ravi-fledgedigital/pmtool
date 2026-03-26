<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Vaimo\AepEventStreaming\Api\ConfigInterface;

class SynchronisePublisher
{
    public const TOPIC_NAME = 'aep.customer.synchronise';

    private PublisherInterface $publisher;
    private ConfigInterface $config;

    public function __construct(
        PublisherInterface $publisher,
        ConfigInterface $config
    ) {
        $this->publisher = $publisher;
        $this->config = $config;
    }

    public function publish(CustomerInterface $customer): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }
        $this->publisher->publish(self::TOPIC_NAME, $customer);
    }
}
