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
use Magento\AdobeCommerceWebhooks\Model\DevRun\WebhookDevRunner;
use Magento\AdobeCommerceWebhooks\Model\WebhookList;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\WebhookBatchRunnerInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Serialize\Serializer\Json;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs a registered webhook for development purposes.
 */
class WebhooksDevRunCommand extends Command
{
    private const ARGUMENT_NAME = 'name';
    private const ARGUMENT_PAYLOAD = 'payload';

    /**
     * @param WebhookList $webhookList
     * @param WebhookBatchRunnerInterface $webhookBatchRunner
     * @param Json $json
     * @param WebhookDevRunner $webhookDevRunner
     * @param string|null $name
     */
    public function __construct(
        private WebhookList $webhookList,
        private WebhookBatchRunnerInterface $webhookBatchRunner,
        private Json $json,
        private WebhookDevRunner $webhookDevRunner,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->addArgument(
            self::ARGUMENT_NAME,
            InputArgument::REQUIRED,
            'Webhook name'
        );
        $this->addArgument(
            self::ARGUMENT_PAYLOAD,
            InputArgument::REQUIRED,
            'The webhook payload in JSON format'
        );

        $this->setName('webhooks:dev:run')
            ->setDescription('Runs a registered webhook for development purposes.');

        parent::configure();
    }

    /**
     * Runs a single webhook for development purposes.
     *
     * For example, you registered the webhook observer.checkout_cart_product_add_before:before
     * instead of executing it via steps in the application
     * you can emulate execution of the webhook by running the next command:
     * webhooks:dev:run observer.checkout_cart_product_add_before:before '{"data":{"product":{"name":"Product1"}}}'
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $webhookName = (string)$input->getArgument(self::ARGUMENT_NAME);
        $webhook = $this->webhookList->get($webhookName);

        if ($webhook === null) {
            $output->writeln(sprintf('The webhook %s is not registered', $webhookName));
            return CLI::RETURN_FAILURE;
        }

        try {
            $payload = $this->json->unserialize($input->getArgument(self::ARGUMENT_PAYLOAD));
            $payload = $this->webhookDevRunner->run($webhook, $payload);
        } catch (\InvalidArgumentException $e) {
            $output->writeln(sprintf('Failed to process payload: %s', $e->getMessage()));

            return CLI::RETURN_FAILURE;
        } catch (Exception $e) {
            $output->writeln(sprintf(
                'Failed to process webhook "%s". Or webhook endpoint returned exception operation. Error: %s',
                $webhook->getName(),
                $e->getMessage()
            ));
            $output->writeln('Check logs for more information.');

            return CLI::RETURN_FAILURE;
        }

        $output->writeln('The webhook was successfully processed.');
        $output->writeln(json_encode($payload, JSON_PRETTY_PRINT));

        return Cli::RETURN_SUCCESS;
    }
}
