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

use Magento\AdobeCommerceWebhooks\Model\Webhook;
use Magento\AdobeCommerceWebhooks\Model\WebhookFactory;
use Magento\AdobeCommerceWebhooks\Model\WebhookInfo\WebhookInfo;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Command for displaying a payload of the specified webhook.
 */
class WebhooksInfoCommand extends Command
{
    public const NAME = 'webhooks:info';
    private const ARGUMENT_WEBHOOK_NAME = 'webhook-name';
    private const ARGUMENT_WEBHOOK_TYPE = 'webhook-type';
    private const OPTION_DEPTH = 'depth';

    /**
     * @param WebhookInfo $webhookInfo
     * @param WebhookFactory $webhookFactory
     * @param string|null $name
     */
    public function __construct(
        private WebhookInfo $webhookInfo,
        private WebhookFactory $webhookFactory,
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
            ->setDescription('Returns the payload of the specified webhook.')
            ->addArgument(
                self::ARGUMENT_WEBHOOK_NAME,
                InputArgument::REQUIRED,
                'Webhook method name'
            )
            ->addArgument(
                self::ARGUMENT_WEBHOOK_TYPE,
                InputArgument::OPTIONAL,
                sprintf('Webhook type (%s)', implode(', ', [Webhook::TYPE_BEFORE, Webhook::TYPE_AFTER])),
                Webhook::TYPE_BEFORE
            )
            ->addOption(
                self::OPTION_DEPTH,
                null,
                InputOption::VALUE_OPTIONAL,
                'The number of levels in the webhook payload to return',
                WebhookInfo::NESTED_LEVEL + 1
            );

        parent::configure();
    }

    /**
     * Returns the payload of the specified event.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            /** @var Webhook $webhook */
            $webhook = $this->webhookFactory->create([
                Webhook::NAME => $input->getArgument(self::ARGUMENT_WEBHOOK_NAME),
                Webhook::TYPE => $input->getArgument(self::ARGUMENT_WEBHOOK_TYPE),
            ]);
            $output->writeln(
                $this->webhookInfo->getJsonInfo(
                    $webhook,
                    (int)$input->getOption(self::OPTION_DEPTH)
                )
            );
        } catch (Throwable $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
