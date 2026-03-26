<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooksSubscriber\Model;

use Magento\AdobeCommerceWebhooks\Model\Config\WebhookConfigurationException;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\WebhookList;

/**
 * Returns list of hooks
 */
class HookList
{
    /**
     * @param WebhookList $webhookList
     * @param HookIdGenerator $hookIdGenerator
     */
    public function __construct(
        private WebhookList $webhookList,
        private HookIdGenerator $hookIdGenerator
    ) {
    }

    /**
     * Returns list of hooks with uniques id as key
     *
     * @return Hook[]
     * @throws WebhookConfigurationException
     */
    public function getAll(): array
    {
        $hooks = [];

        foreach ($this->webhookList->getAll() as $webhook) {
            foreach ($webhook->getBatches() as $batch) {
                foreach ($batch->getHooks() as $hook) {
                    $id = $this->hookIdGenerator->generate(
                        $webhook->getName(),
                        $webhook->getType(),
                        $batch->getName(),
                        $hook->getName()
                    );

                    $hooks[$id] = $hook;
                }
            }
        }

        return $hooks;
    }

    /**
     * Retrieves the hook with the provided ID
     *
     * @param string $hookId
     * @return Hook|null
     */
    public function getById(string $hookId): ?Hook
    {
        try {
            return $this->getAll()[$hookId] ?? null;
        } catch (WebhookConfigurationException $e) {
            return null;
        }
    }
}
