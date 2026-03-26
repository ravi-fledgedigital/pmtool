<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServerNewRelic\Plugin;

use Magento\ApplicationServer\App\Application;

/**
 * This plugin is used to monitor request metrics for App Server in New Relic.
 */
class NewRelicRequestMonitoringPlugin
{
    private const NEWRELIC_APPNAME = 'newrelic.appname';

    /**
     * Plugin for monitoring request metrics in New Relic.
     *
     * @param Application $subject
     * @param callable $proceed
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundLaunch(Application $subject, callable $proceed)
    {
        if (!extension_loaded('newrelic')) {
            return $proceed();
        }
        newrelic_end_transaction();
        newrelic_start_transaction(ini_get(self::NEWRELIC_APPNAME));
        newrelic_background_job(false);
        $response = $proceed();
        newrelic_end_transaction();
        return $response;
    }
}
