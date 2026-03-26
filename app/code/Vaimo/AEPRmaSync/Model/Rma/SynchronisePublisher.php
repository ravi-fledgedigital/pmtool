<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPRmaSync\Model\Rma;

use Magento\Framework\MessageQueue\PublisherInterface;
use Vaimo\AepEventStreaming\Api\ConfigInterface;
use Amasty\Rma\Api\Data\RequestInterface;

class SynchronisePublisher
{
    public const TOPIC_NAME = 'aep.rma.synchronise';

    private PublisherInterface $publisher;
    private ConfigInterface $config;

    public function __construct(
        PublisherInterface $publisher,
        ConfigInterface $config
    ) {
        $this->publisher = $publisher;
        $this->config = $config;
    }

    public function publish(RequestInterface $rma): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $this->publisher->publish(self::TOPIC_NAME, $rma);
    }
}