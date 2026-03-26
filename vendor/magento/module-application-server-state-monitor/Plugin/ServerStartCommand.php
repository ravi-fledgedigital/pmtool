<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServerStateMonitor\Plugin;

use Magento\ApplicationServer\Console\ServerStartCommand as ServerStartCommandOriginal;
use Magento\ApplicationServer\ObjectManager\AppObjectManagerFactory;
use Magento\ApplicationServerStateMonitor\ObjectManager\AppObjectManager;
use Magento\ApplicationServerStateMonitor\StateMonitor\Config as StateMonitorConfig;
use Magento\Framework\ObjectManager\Resetter\ResetterFactory;
use Magento\Framework\TestFramework\ApplicationStateComparator\Resetter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Plugin that sets up ApplicationManager for StateMonitor when it is enabled
 *
 * Adds `--state-monitor` option to `server:run` command.
 */
class ServerStartCommand
{
    private const OPTION_STATE_MONITOR = 'state-monitor';

    /**
     * Prepares AppObjectManagerFactory & ResetterFactory to use customized ObjectManager/Resetter for monitoring state
     *
     * @param ServerStartCommandOriginal $subject
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(ServerStartCommandOriginal $subject, InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption(static::OPTION_STATE_MONITOR)) {
            return null;
        }
        AppObjectManagerFactory::setLocatorClassNameOverride(AppObjectManager::class);
        ResetterFactory::setResetterClassName(Resetter::class);
        StateMonitorConfig::setEnabled(true);
        return null;
    }

    /**
     * Gets after options list
     *
     * @param ServerStartCommandOriginal $subject
     * @param array $options
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetOptionsList(ServerStartCommandOriginal $subject, array $options) : array
    {
        $options[] = new InputOption(
            static::OPTION_STATE_MONITOR,
            null,
            InputOption::VALUE_NONE,
            'Enable state monitoring. Use this only for debugging state issues!'
        );
        return $options;
    }
}
