<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Console\Command;

use Exception;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Collector\CollectorInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Module\Dir;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Collects list of supported webhook method names for the provided module
 */
class WebhooksListAllCommand extends Command
{
    public const NAME = 'webhooks:list:all';

    private const ARGUMENT_MODULE_NAME = 'module_name';

    /**
     * @param CollectorInterface $collector
     * @param Dir $dir
     * @param string|null $name
     */
    public function __construct(
        private CollectorInterface $collector,
        private Dir $dir,
        private ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Returns a list of supported webhook method names for the specified module')
            ->addArgument(
                self::ARGUMENT_MODULE_NAME,
                InputArgument::REQUIRED,
                'Module name'
            );

        parent::configure();
    }

    /**
     * Collects and returns the list of supported webhook methods names for the provided module.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $modulePath = $this->dir->getDir($input->getArgument(self::ARGUMENT_MODULE_NAME));
            $events = $this->collector->collect($modulePath);
            ksort($events);
            foreach ($events as $eventData) {
                $output->writeln($eventData->getEventName());
            }

            return Cli::RETURN_SUCCESS;
        } catch (Exception $e) {
            $output->writeln($e->getMessage());

            return Cli::RETURN_FAILURE;
        }
    }
}
