<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepBase\Cron;

use Vaimo\AepBase\Api\ConfigInterface;
use Vaimo\AepBase\Model\AbandonedCarts\ProcessorFactory;

class AbandonedCarts
{
    private ConfigInterface $config;
    private ProcessorFactory $processorFactory;

    public function __construct(
        ConfigInterface $config,
        ProcessorFactory $processorFactory
    ) {
        $this->config = $config;
        $this->processorFactory = $processorFactory;
    }

    public function execute(): void
    {
        if (!$this->config->isDataAggregationEnabled()) {
            return;
        }

        $processor = $this->processorFactory->create();
        $dateTo = new \DateTime();
        $dateTo->setTime((int) $dateTo->format('H'), 0, 0, 0);
        $dateFrom = clone $dateTo;
        $dateFrom->sub(new \DateInterval('PT2H'));

        $processor->process($dateFrom, $dateTo);
    }
}
