<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Psr\Log\LoggerInterface;
use Vaimo\AepEventStreaming\Api\ConfigInterface;
use Vaimo\AepEventStreaming\Exception\CustomerSyncException;
use Vaimo\AepEventStreaming\Exception\MissingActionIdException;

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

    public function processMessage(CustomerInterface $customer): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        try {
            $this->synchroniseService->syncWithAep($customer);
        } catch (CustomerSyncException | MissingActionIdException $e) {
            $this->logger->critical($e);
        }
    }
}
