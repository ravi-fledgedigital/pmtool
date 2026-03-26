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

namespace Magento\AdobeCommerceWebhooks\Model;

use Magento\AdobeCommerceWebhooks\Model\Config\WebhookConfigurationException;
use Magento\AdobeCommerceWebhooks\Model\Config\WebhookLoader;
use Magento\AdobeCommerceWebhooks\Model\Config\WebhookNameGenerator;

/**
 * Returns list of all subscribed webhooks
 */
class WebhookList
{
    /**
     * @var Webhook[]|null
     */
    private ?array $webhooks = null;

    /**
     * @param WebhookLoader $webhookLoader
     * @param WebhookNameGenerator $webhookNameGenerator
     */
    public function __construct(
        private WebhookLoader $webhookLoader,
        private WebhookNameGenerator $webhookNameGenerator
    ) {
    }

    /**
     * Returns a list all subscribed webhooks
     *
     * @return Webhook[]
     * @throws WebhookConfigurationException
     */
    public function getAll(): array
    {
        if ($this->webhooks === null) {
            $this->load();
        }

        return $this->webhooks;
    }

    /**
     * Returns webhook based on provided name and type or returns null if such webhook is not registered.
     *
     * @param string $webhookName
     * @param string|null $webhookType
     * @return Webhook|null
     * @throws WebhookConfigurationException
     */
    public function get(string $webhookName, ?string $webhookType = null): ?Webhook
    {
        if ($this->webhooks === null) {
            $this->load();
        }

        if ($webhookType !== null) {
            $webhookName = $this->webhookNameGenerator->generate($webhookName, $webhookType);
        }

        return $this->webhooks[$webhookName] ?? null;
    }

    /**
     * Creates webhook objects based on configuration and stored into private property
     *
     * @return void
     * @throws WebhookConfigurationException
     */
    private function load(): void
    {
        if ($this->webhooks !== null) {
            return;
        }

        $this->webhooks = $this->webhookLoader->load();
    }
}
