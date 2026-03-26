<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\Console;

use Magento\ApplicationServer\App\HttpContextFactory;
use Magento\ApplicationServer\App\ServerFactory;
use Magento\Framework\Console\Cli;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ServerStartCommand
 *
 * Command for starting the Application Server
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ServerStartCommand extends Command
{
    /**
     * Constants for CLI command options.
     */
    private const OPTION_PORT = 'port';
    private const OPTION_AREA = 'area';
    private const OPTION_BACKGROUND = 'background';
    private const OPTION_WORKER_NUM = 'workerNum';
    private const OPTION_DISPATCH_MODE = 'dispatchMode';
    private const OPTION_MAX_REQUESTS = 'maxRequests';
    private const OPTION_MAX_WAIT_TIME = 'maxWaitTime';
    private const OPTION_TICK_INTERVAL_MS = 'tickIntervalMs';

    /**
     * Environment variable names for options values.
     */
    private const ENVIRONMENT_DISABLE_TICK = 'APPLICATION_SERVER_DISABLE_TICK';
    /* Note: For now, we only enable the option of timer tick that checks for configuration changes in parent if
    this environment variable is set.  Otherwise, it will be disabled. */
    private const ENVIRONMENT_ENABLE_TICK = 'APPLICATION_SERVER_ENABLE_TICK';

    /**
     * Constants for default values of CLI command options.
     *
     * Documentation for configuration values https://openswoole.com/docs/modules/swoole-server/configuration
     */
    private const DEFAULT_PORT = 9501;
    private const DEFAULT_AREA = 'graphql';
    private const DEFAULT_BACKGROUND = 0;
    private const DEFAULT_WORKER_NUM = 4;
    private const DEFAULT_MAX_REQUEST = 10000;
    private const DEFAULT_DISPATCH_MODE = 3; // 3 = preemptive mode
    private const DEFAULT_MAX_WAIT_TIME = 60 * 60; // one hour
    private const DEFAULT_TICK_INTERVAL_MS = 1_000; // one second

    /**
     * ServerStartCommand constructor
     *
     * @param HttpContextFactory $httpContextFactory
     * @param ServerFactory $serverFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private HttpContextFactory $httpContextFactory,
        private ServerFactory $serverFactory,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('server:run')
            ->setDescription('Run application server')
            ->setDefinition($this->getOptionsList());
    }

    /**
     * Get list of options
     *
     * @return array
     */
    public function getOptionsList(): array
    {
        $optionList = [
            new InputOption(
                self::OPTION_PORT,
                'p',
                InputOption::VALUE_OPTIONAL,
                'port to serv on',
                self::DEFAULT_PORT
            ),
            new InputOption(
                self::OPTION_BACKGROUND,
                'b',
                InputOption::VALUE_OPTIONAL,
                'background mode flag',
                self::DEFAULT_BACKGROUND
            ),
            new InputOption(
                self::OPTION_WORKER_NUM,
                'wn',
                InputOption::VALUE_OPTIONAL,
                'number of worker processes to start',
                $this->getCpuNum()
            ),
            new InputOption(
                self::OPTION_DISPATCH_MODE,
                'dm',
                InputOption::VALUE_OPTIONAL,
                'mode of dispatching connections to the worker processes',
                self::DEFAULT_DISPATCH_MODE
            ),
            new InputOption(
                self::OPTION_MAX_REQUESTS,
                'mr',
                InputOption::VALUE_OPTIONAL,
                'max requests before worker process would be restarted',
                self::DEFAULT_MAX_REQUEST
            ),
            new InputOption(
                self::OPTION_AREA,
                'a',
                InputOption::VALUE_OPTIONAL,
                'application server area',
                self::DEFAULT_AREA
            ),
            new InputOption(
                'magento-init-params',
                'mip',
                InputOption::VALUE_OPTIONAL,
                'magento bootstrap init params',
                ''
            ),
            new InputOption(
                self::OPTION_MAX_WAIT_TIME,
                'mwt',
                InputOption::VALUE_OPTIONAL,
                'how long to wait for workers after reload (eg. config change) before killing them',
                self::DEFAULT_MAX_WAIT_TIME
            )
        ];
        if (getenv(self::ENVIRONMENT_ENABLE_TICK)) { // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $optionList[] = new InputOption(
                self::OPTION_TICK_INTERVAL_MS,
                'tick',
                InputOption::VALUE_OPTIONAL,
                'interval time in ms to check a configuration change in the parent thread. 0 = disabled',
                self::DEFAULT_TICK_INTERVAL_MS,
            );
        }
        return $optionList;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->isSwooleExtensionInstalled()) {
            $output->writeln('<error>Swoole extension is not installed. Please read documentation.</error>');
            return Cli::RETURN_FAILURE;
        }
        $port = (int)$input->getOption(self::OPTION_PORT);
        $daemonize = (bool)$input->getOption(self::OPTION_BACKGROUND);
        $params = [
            'daemonize' => $daemonize,
            'worker_num' => (int)$input->getOption(self::OPTION_WORKER_NUM),
            'dispatch_mode' => (int)$input->getOption(self::OPTION_DISPATCH_MODE),
            'max_request' => (int)$input->getOption(self::OPTION_MAX_REQUESTS),
            'max_wait_time' => (int)$input->getOption(self::OPTION_MAX_WAIT_TIME),
            'log_level' => \SWOOLE_LOG_DEBUG,
            'enable_coroutine' => false,
        ];
        $areaCode = (string)$input->getOption(self::OPTION_AREA);
        if (getenv(self::ENVIRONMENT_ENABLE_TICK)) { // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $tickIntervalValue = (int)$input->getOption(self::OPTION_TICK_INTERVAL_MS);
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $tickIntervalMs = getenv(self::ENVIRONMENT_DISABLE_TICK) ? 0 : $tickIntervalValue;
        } else {
            $tickIntervalMs = 0;
        }
        $logger = $this->logger;
        $needToRestoreVerbosity = false;
        if (!$daemonize) {
            $verbosity = $output->getVerbosity();
            /* Note: We change default verbosity because we should have INFO and NOTICE output to console by default */
            switch ($verbosity) {
                case $output::VERBOSITY_NORMAL:
                    $output->setVerbosity($output::VERBOSITY_VERY_VERBOSE);
                    $needToRestoreVerbosity = true;
                    break;
                case $output::VERBOSITY_VERBOSE:
                case $output::VERBOSITY_VERY_VERBOSE:
                    $output->setVerbosity($output::VERBOSITY_DEBUG);
                    $needToRestoreVerbosity = true;
                    break;
            }
            $logger = new ConsoleLogger($output);
        }

        $server = $this->serverFactory->create($port, $params);
        $context = $this->httpContextFactory->create(['logger' => $logger]);
        $context->attach($server, $areaCode, $tickIntervalMs);
        $logger->log(LogLevel::NOTICE, "Start server for '$areaCode' area");
        $server->start();
        $logger->log(LogLevel::NOTICE, "Ending server for '$areaCode' area");
        if ($needToRestoreVerbosity) {
            // @phpstan-ignore-next-line
            $output->setVerbosity($verbosity);
        }
        return Cli::RETURN_SUCCESS;
    }

    /**
     * Check if Swoole extension is installed
     *
     * @return bool
     */
    private function isSwooleExtensionInstalled(): bool
    {
        return (extension_loaded('swoole') || extension_loaded('openswoole'));
    }

    /**
     * Provides CPU number
     *
     * @return int
     */
    private function getCpuNum(): int
    {
        //get cpu number using swoole_cpu_num
        if (\function_exists('swoole_cpu_num')) {
            return \swoole_cpu_num();
        }

        //get cpu number for OpenSwoole v22.*
        if (class_exists(\OpenSwoole\Util::class)
            && method_exists(\OpenSwoole\Util::class, 'getCPUNum')
        ) {
            return \OpenSwoole\Util::getCPUNum();
        }

        //return default cpu number
        return self::DEFAULT_WORKER_NUM;
    }
}
