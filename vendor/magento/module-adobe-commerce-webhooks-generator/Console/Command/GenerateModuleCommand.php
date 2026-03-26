<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooksGenerator\Console\Command;

use Exception;
use Magento\AdobeCommerceWebhooksGenerator\Console\Command\GenerateModule\Generator;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for generating a module based on a list of registered webhooks
 */
class GenerateModuleCommand extends Command
{
    public const NAME = 'webhooks:generate:module';

    /**
     * @param Generator $generator
     * @param DirectoryList $directoryList
     * @param string|null $name
     */
    public function __construct(
        private Generator $generator,
        private DirectoryList $directoryList,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Generate plugins based on webhook registrations');

        parent::configure();
    }

    /**
     * Runs module generation.
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
            $appCodeDirectory = $this->directoryList->getPath(DirectoryList::APP) . '/code';

            $this->generator->run($appCodeDirectory);

            $output->writeln('Module was generated in the app/code/Magento directory');

            return Cli::RETURN_SUCCESS;
        } catch (Exception $e) {
            $output->writeln($e->getMessage());

            return Cli::RETURN_FAILURE;
        }
    }
}
