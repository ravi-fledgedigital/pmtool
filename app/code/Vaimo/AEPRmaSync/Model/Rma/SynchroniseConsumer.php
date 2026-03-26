<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPRmaSync\Model\Rma;

use Psr\Log\LoggerInterface;
use Vaimo\AepEventStreaming\Api\ConfigInterface;
use Vaimo\AepEventStreaming\Exception\AepException;
use Amasty\Rma\Api\Data\RequestInterface;

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

    public function processMessage(RequestInterface $rma): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        try {
            $this->synchroniseService->syncWithAep($rma);
        } catch (AepException $e) {
            $this->logger->critical($e);
        }
    }
}