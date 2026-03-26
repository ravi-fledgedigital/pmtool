<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogSyncAdmin\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\ServicesId\Model\ServicesClientInterface;
use Magento\ServicesId\Model\ServicesConfigInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for requesting a catalog domain cleanup
 */
class CatalogCleanup extends Command
{
    private const COMMAND_NAME = 'saas:catalog:cleanup';

    /**
     * @var ServicesConfigInterface
     */
    private ServicesConfigInterface $servicesConfig;

    /**
     * @var ServicesClientInterface
     */
    private ServicesClientInterface $servicesClient;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ServicesConfigInterface $servicesConfig
     * @param ServicesClientInterface $servicesClient
     * @param LoggerInterface $logger
     */
    public function __construct(
        ServicesConfigInterface $servicesConfig,
        ServicesClientInterface $servicesClient,
        LoggerInterface $logger
    ) {
        $this->servicesConfig = $servicesConfig;
        $this->servicesClient = $servicesClient;
        $this->logger = $logger;
        parent::__construct();
    }
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Requests a catalog cleanup');
        parent::configure();
    }

    /**
     * Request a catalog domain cleanup to the data-management service
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Initiating cleanup process ...</info>');
        $environmentId = $this->servicesConfig->getEnvironmentId();
        if (!empty($environmentId) && $this->servicesConfig->isApiKeySet()) {
            $output->writeln(sprintf('<info>Requesting cleanup for environment %s</info>', $environmentId));
            try {
                $response = $this->servicesClient->request('POST', $this->getUrl($environmentId));
                if ($response
                    && !empty($response['status'])
                    && $response['status'] != 200
                ) {
                    $this->logger->error('Unable to request cleanup environment.', ['response' => $response]);
                    $errorMessage = !empty($response['message']) ? $response['message'] : $response['error'];
                    $output->writeln(sprintf('<error>An error occurred requesting the cleanup: %s </error>', $errorMessage));
                    $exitCode = Cli::RETURN_FAILURE;
                }
                else {
                    $output->writeln("<info>Cleanup successfully requested</info>");
                    $exitCode = Cli::RETURN_SUCCESS;
                }
            } catch (\Exception $exception) {
                $this->logger->error('Unable to request cleanup environment.', ['error' => $exception]);
                $output->writeln(sprintf('<error>An error occurred requesting the cleanup: %s </error>', $exception->getMessage()));
                $exitCode = Cli::RETURN_FAILURE;
            }
        }
        else {
            $output->writeln('<error>SaaS configuration not properly set</error>');
            $exitCode = Cli::RETURN_FAILURE;
        }
        $output->writeln("<info>End of cleanup request process</info>");
        return $exitCode;
    }

    /**
     * Build registry API url
     *
     * @param string $environmentId
     * @return string
     */
    private function getUrl(string $environmentId): string
    {
        $path = sprintf(
            'registry/environments/%s/cleanup',
            $environmentId
        );
        return $this->servicesConfig->getRegistryApiUrl($path);
    }
}
