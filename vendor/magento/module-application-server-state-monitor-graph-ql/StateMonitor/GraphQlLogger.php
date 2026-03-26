<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServerStateMonitorGraphQl\StateMonitor;

use Magento\ApplicationServerStateMonitor\StateMonitor\Config;
use Magento\ApplicationServerStateMonitor\StateMonitor\Config as StateMonitorConfig;
use Magento\ApplicationServerStateMonitor\StateMonitor\RequestNameInterface;
use Magento\GraphQl\Model\Query\Logger\LoggerInterface;

/**
 * Logs GraphQlOperationNames as requst name for StateMonitor
 */
class GraphQlLogger implements LoggerInterface, RequestNameInterface
{
    /**
     * @var string
     */
    private string $requestName = '';

    /**
     * @param StateMonitorConfig $stateMonitorConfig
     */
    public function __construct(
        private readonly StateMonitorConfig $stateMonitorConfig,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(array $queryDetails) : void
    {
        if (!$this->stateMonitorConfig->isEnabled()) {
            return;
        }
        $this->requestName = $queryDetails['GraphQlOperationNames'] ?? '';
    }

    /**
     * @inheritdoc
     */
    public function resetState() : void
    {
        $this->requestName = '';
    }

    /**
     * Gets request name
     *
     * @return string
     */
    public function getRequestName() : string
    {
        $returnValue = $this->requestName;
        $this->resetState();
        return $returnValue;
    }
}
