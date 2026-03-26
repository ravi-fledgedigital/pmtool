<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServerStateMonitor\Plugin;

use Magento\ApplicationServer\App\HttpContext as AppHttpContext;
use Magento\ApplicationServerStateMonitor\ObjectManager\AppObjectManager;
use Magento\ApplicationServerStateMonitor\StateMonitor\Config as StateMonitorConfig;
use Magento\ApplicationServerStateMonitor\StateMonitor\StateMonitor;
use Magento\Framework\ObjectManagerInterface;
use Swoole\Http\Server;

/**
 * Plugin to run StateMonitor after Request has been processed
 */
class HttpContext
{
    /**
     * @param StateMonitorConfig $stateMonitorConfig
     */
    public function __construct(
        private readonly StateMonitorConfig $stateMonitorConfig,
    ) {
    }

    /**
     * Do the state monitor if enabled
     *
     * @param AppHttpContext $appHttpContext
     * @param mixed $result
     * @param mixed $request
     * @param mixed $swooleResponse
     * @param ObjectManagerInterface $objectManager
     * @param Server $server
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcessRequest(
        AppHttpContext $appHttpContext,
        $result,
        $request,
        $swooleResponse,
        ObjectManagerInterface $objectManager,
        Server $server
    ) : void {
        if ((!($objectManager instanceof AppObjectManager)) || !$this->stateMonitorConfig->isEnabled()) {
            return;
        }
        $stateMonitor = $objectManager->get(StateMonitor::class);
        $stateMonitor->execute();
        $server->stop(); // For memory considerations, we don't reuse the worker threads in this mode.
    }
}
