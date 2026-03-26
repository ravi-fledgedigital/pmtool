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

namespace Magento\AdobeCommerceWebhooks\Model\WebhookRunner;

use Magento\AdobeCommerceWebhooks\Model\Webhook\Batch;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationInterface;

/**
 * Runs batch of webhooks based on its configuration
 */
interface WebhookBatchRunnerInterface
{
    /**
     * Executes a batch of webhooks and returns list of operations based on webhooks responses
     *
     * @param Batch $batch
     * @param array $webhookData
     * @return OperationInterface[]
     * @throws WebhookBatchRunnerException
     */
    public function execute(Batch $batch, array $webhookData): array;
}
