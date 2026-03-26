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

namespace Magento\AdobeCommerceWebhooksSubscriber\Model\WebhookSubscriber;

use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface;
use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterface;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Config\HookNameGenerator;
use Magento\AdobeCommerceWebhooksSubscriber\Model\HookRepository;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;

/**
 * Updates batch order for all hooks in the same batch
 */
class BatchOrderUpdater implements BatchOrderUpdaterInterface
{
    /**
     * @param DeploymentConfig $deploymentConfig
     * @param Writer $configWriter
     * @param HookNameGenerator $hookNameGenerator
     */
    public function __construct(
        private readonly DeploymentConfig $deploymentConfig,
        private readonly Writer $configWriter,
        private readonly HookNameGenerator $hookNameGenerator,
    ) {
    }

    /**
     * Sets batch order for all hooks in the same batch to the same value defined for the new hook
     *
     * @param HookInterface $newHook
     * @return void
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function execute(HookInterface $newHook): void
    {
        $webhooks = $this->deploymentConfig->get(HookRepository::WEBHOOKS_CONFIG_NAME, []);
        $webhookName = $this->hookNameGenerator->generate($newHook);

        if (!isset($webhooks[$webhookName][$newHook->getBatchName()])) {
            return;
        }

        foreach ($webhooks[$webhookName][$newHook->getBatchName()] as &$hook) {
            if ($newHook->getBatchOrder() !== ($hook[WebhookDataInterface::BATCH_ORDER] ?? 0)) {
                $hook[WebhookDataInterface::BATCH_ORDER] = $newHook->getBatchOrder();
            }
        }

        $this->configWriter->saveConfig(
            [
                ConfigFilePool::APP_ENV => [
                    HookRepository::WEBHOOKS_CONFIG_NAME => $webhooks
                ]
            ],
            true
        );
    }
}
