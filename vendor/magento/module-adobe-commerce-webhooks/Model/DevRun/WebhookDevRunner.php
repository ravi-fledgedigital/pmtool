<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
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

namespace Magento\AdobeCommerceWebhooks\Model\DevRun;

use Magento\AdobeCommerceWebhooks\Model\Webhook;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationException;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\WebhookBatchRunnerException;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\WebhookBatchRunnerInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class for running a webhook given a prepared payload
 */
class WebhookDevRunner
{
    /**
     * @param WebhookBatchRunnerInterface $webhookBatchRunner
     */
    public function __construct(
        private WebhookBatchRunnerInterface $webhookBatchRunner,
    ) {
    }

    /**
     * Runs the input webhook with the input request payload
     *
     * @param Webhook $webhook
     * @param array $payload
     * @return array
     * @throws WebhookBatchRunnerException
     * @throws OperationException
     * @throws LocalizedException
     */
    public function run(Webhook $webhook, array $payload): array
    {
        foreach ($webhook->getBatches() as $webhookBatch) {
            $operations = $this->webhookBatchRunner->execute($webhookBatch, $payload);
            foreach ($operations as $hookOperations) {
                foreach ($hookOperations as $operation) {
                    $operation->execute($payload);
                }
            }
        }

        return $payload;
    }
}
