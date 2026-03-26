<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2025 Adobe
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

namespace Magento\AdobeCommerceWebhooks\Model;

use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface;
use Magento\AdobeCommerceWebhooks\Api\WebhookSubscriptionListInterface;
use Magento\AdobeCommerceWebhooks\Model\Data\HookToWebhookDataConverter;

/**
 * @inheritDoc
 */
class WebhookSubscriptionList implements WebhookSubscriptionListInterface
{
    /**
     * @param WebhookList $webhookList
     * @param HookToWebhookDataConverter $converter
     */
    public function __construct(
        private readonly WebhookList $webhookList,
        private readonly HookToWebhookDataConverter $converter
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getList(): array
    {
        return $this->processWebhooks($this->webhookList->getAll());
    }

    /**
     * Process all subscribed webhooks.
     *
     * @param array $webhooks
     * @return WebhookDataInterface[]
     */
    private function processWebhooks(array $webhooks): array
    {
        $subscribedWebhooks = [];

        foreach ($webhooks as $webhook) {
            foreach ($webhook->getBatches() as $batch) {
                foreach ($batch->getHooks() as $hook) {
                    if (!$hook->shouldRemove()) {
                        $subscribedWebhooks[] = $this->converter->convert($hook);
                    }
                }
            }
        }

        return $subscribedWebhooks;
    }
}
