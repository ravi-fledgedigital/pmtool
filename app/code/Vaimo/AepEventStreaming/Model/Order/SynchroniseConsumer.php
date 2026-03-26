<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;
use Vaimo\AepEventStreaming\Api\ConfigInterface;
use Vaimo\AepEventStreaming\Exception\AepException;

class SynchroniseConsumer
{
    private Synchronise $synchroniseService;
    private ConfigInterface $config;
    private LoggerInterface $logger;

    public function __construct(
        Synchronise $synchroniseService,
        ConfigInterface $config,
        LoggerInterface $logger
    ) {
        $this->synchroniseService = $synchroniseService;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function processMessage(OrderInterface $order): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        try {
            $this->synchroniseService->syncWithAep($order);
        } catch (AepException $e) {
            $this->logger->critical($e);
        }
    }
}
