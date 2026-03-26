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

/**
 * Returns webhooks configuration from xml configuration files
 */
class WebhookXmlConfigurationSource implements WebhookConfigurationSourceInterface
{
    /**
     * @param Reader $reader
     * @param WebhookNameGenerator $webhookNameGenerator
     */
    public function __construct(
        private Reader $reader,
        private WebhookNameGenerator $webhookNameGenerator
    ) {
    }

    /**
     * Returns webhooks configuration from xml configuration files
     *
     * @return array
     */
    public function getConfig(): array
    {
        $webhookConfigs = [];

        foreach ($this->reader->read() as $webhookConfig) {
            $name = $this->webhookNameGenerator->generate($webhookConfig[Webhook::NAME], $webhookConfig[Webhook::TYPE]);
            $webhookConfigs[$name] = $webhookConfig;
        }

        return $webhookConfigs;
    }
}
