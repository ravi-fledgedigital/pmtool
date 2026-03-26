<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServerNewRelic\Plugin;

use Magento\ApplicationServer\Console\ServerStartCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This plugin disables New Relic on command start for ApplicationServer.
 */
class DisableNewRelicOnCommandStart
{
    /**
     * Disables New Relic transaction before starting the command.
     *
     * Note: The New Relic transactions begin again later for each request that comes in.
     *
     * @param ServerStartCommand $subject
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeRun(ServerStartCommand $subject, InputInterface $input, OutputInterface $output)
    {
        if (extension_loaded('newrelic')) {
            newrelic_end_transaction(true);
        }
        return null;
    }
}
