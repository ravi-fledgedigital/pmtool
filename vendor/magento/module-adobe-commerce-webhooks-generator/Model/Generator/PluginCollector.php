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

namespace Magento\AdobeCommerceWebhooksGenerator\Model\Generator;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\Collector\ApiServiceCollector;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\Collector\CollectorException;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\Collector\ResourceModelCollector;

/**
 * Collects information for plugin type webhooks.
 */
class PluginCollector
{
    /**
     * @param ApiServiceCollector $apiServiceCollector
     * @param PluginConverter $pluginConverter
     * @param ResourceModelCollector $resourceModelCollector
     */
    public function __construct(
        private ApiServiceCollector $apiServiceCollector,
        private PluginConverter $pluginConverter,
        private ResourceModelCollector $resourceModelCollector,
    ) {
    }

    /**
     * Collects information for plugin type webhooks.
     *
     * @param string $webhookName
     * @param string $webhookType
     * @return array
     * @throws CollectorException
     */
    public function collect(string $webhookName, string $webhookType): array
    {
        if (str_contains($webhookName, 'resource_model')) {
            $resourceModel = $this->resourceModelCollector->collect($webhookName);
            return $this->pluginConverter->convert(
                $resourceModel,
                $webhookName,
                $webhookType,
                PluginConverter::TYPE_RESOURCE_MODEL
            );
        } else {
            $apiInterface = $this->apiServiceCollector->collect($webhookName);
            return $this->pluginConverter->convert(
                $apiInterface,
                $webhookName,
                $webhookType,
                PluginConverter::TYPE_API_INTERFACE
            );
        }
    }
}
