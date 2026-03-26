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
use Magento\AdobeCommerceWebhooks\Model\Webhook\Batch;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\WebhookList;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Shows list of subscribed webhooks
 */
class WebhooksListCommand extends Command
{
    /**
     * @param WebhookList $webhookList
     * @param string|null $name
     */
    public function __construct(
        private WebhookList $webhookList,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('webhooks:list')
            ->setDescription('Shows list of subscribed webhooks');

        parent::configure();
    }

    /**
     * Displays a list of subscribed webhooks.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $webhooks = $this->webhookList->getAll();

        $table = new Table($output);

        foreach ($webhooks as $webhook) {
            $batches = [];
            foreach ($webhook->getBatches() as $batch) {
                $formattedBatch = $this->formatBatch($batch);
                if (!empty($formattedBatch)) {
                    $batches[$batch->getName()] = $formattedBatch;
                }
            }

            if (empty($batches)) {
                continue;
            }

            $table->setHeaders([
                Webhook::NAME,
                Webhook::TYPE,
                Webhook::BATCHES,
            ]);
            $table->addRow([
                Webhook::NAME => $webhook->getName(),
                Webhook::TYPE => $webhook->getType(),
                Webhook::BATCHES => json_encode($batches, JSON_PRETTY_PRINT),
            ]);
        }

        $table->render();

        return Cli::RETURN_SUCCESS;
    }

    /**
     * Format webhook batch to display in the table
     *
     * @param Batch $batch
     * @return array
     */
    private function formatBatch(Batch $batch): array
    {
        $hooks = [];

        foreach ($batch->getHooks() as $hookObject) {
            if ($hookObject->shouldRemove()) {
                continue;
            }

            $hook = [
                Hook::NAME => $hookObject->getName(),
                Hook::URL => $hookObject->getUrl(),
                Hook::METHOD => $hookObject->getMethod(),
            ];

            $headers = array_filter(
                $hookObject->getHeaders(),
                fn ($header) => !$header->shouldRemove()
            );
            if (!empty($headers)) {
                $hook[Hook::HEADERS] = array_map(
                    fn ($header) => $header->getResolver() ?: $header->getValue(),
                    $headers
                );
            }

            $fields = array_filter(
                $hookObject->getFields(),
                fn ($field) => !$field->shouldRemove()
            );
            if (!empty($fields)) {
                $hook[Hook::FIELDS] = array_map(
                    fn ($field) => $field->getSource() ? $field->getSource() : '',
                    $fields
                );
            }

            $rules = $hookObject->getActiveRules();
            
            if (!empty($rules)) {
                $hook[Hook::RULES] = array_map(
                    fn (Webhook\HookRule $rule) => sprintf(
                        '%s:%s%s',
                        $rule->getField(),
                        $rule->getOperator(),
                        $rule->getValue() ? ':' . $rule->getValue() : ''
                    ),
                    array_values($rules)
                );
            }

            $hooks[] = $hook;
        }

        return $hooks;
    }
}
