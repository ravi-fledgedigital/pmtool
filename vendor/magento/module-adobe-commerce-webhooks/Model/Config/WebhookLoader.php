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

namespace Magento\AdobeCommerceWebhooks\Model\Config;

use Magento\AdobeCommerceWebhooks\Model\Webhook;
use Magento\AdobeCommerceWebhooks\Model\WebhookFactory;

/**
 * Returns merged webhook configuration from all registered sources
 */
class WebhookLoader
{
    /**
     * @param WebhookConfigurationSourcePool $webhookConfigurationSourcePool
     * @param WebhookFactory $webhookFactory
     */
    public function __construct(
        private WebhookConfigurationSourcePool $webhookConfigurationSourcePool,
        private WebhookFactory $webhookFactory
    ) {
    }

    /**
     * Returns merged webhook configuration from all registered sources
     *
     * @return Webhook[]
     * @throws WebhookConfigurationException
     */
    public function load(): array
    {
        $mergedConfig = [];

        foreach ($this->webhookConfigurationSourcePool->getSources() as $source) {
            $mergedConfig = array_replace_recursive($mergedConfig, $source->getConfig());
        }

        $webhooks = [];
        foreach ($mergedConfig as $webhookName => $webhookConfig) {
            $webhooks[$webhookName] = $this->webhookFactory->create($webhookConfig);
        }

        return $webhooks;
    }
}
