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
use Magento\AdobeCommerceWebhooks\Model\Config\WebhookNameGenerator;
use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterface;
use Magento\AdobeCommerceWebhooksSubscriber\Api\HookRepositoryInterface;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Config\HookNameGenerator;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;

/**
 * Repository for managing hooks
 */
class HookRepository implements HookRepositoryInterface
{
    public const WEBHOOKS_CONFIG_NAME = 'webhooks';

    /**
     * @param DeploymentConfig $deploymentConfig
     * @param Writer $configWriter
     * @param HookFactory $hookFactory
     * @param HookNameGenerator $hookNameGenerator
     * @param WebhookNameGenerator $webhookNameGenerator
     */
    public function __construct(
        private readonly DeploymentConfig $deploymentConfig,
        private readonly Writer $configWriter,
        private readonly HookFactory $hookFactory,
        private readonly HookNameGenerator $hookNameGenerator,
        private readonly WebhookNameGenerator $webhookNameGenerator,
    ) {
    }

    /**
     * Saves the hook to the deployment configuration
     *
     * @param HookInterface $hook
     * @return HookInterface
     * @throws WebhookConfigurationException
     * @throws RuntimeException
     * @throws FileSystemException
     */
    public function save(HookInterface $hook): HookInterface
    {
        $webhooks = $this->deploymentConfig->get(self::WEBHOOKS_CONFIG_NAME, []);

        $webhookName = $this->hookNameGenerator->generate($hook);
        $webhooks[$webhookName][$hook->getBatchName()][$hook->getHookName()] = $hook->getHookData();

        $this->configWriter->saveConfig(
            [
                ConfigFilePool::APP_ENV => [
                    self::WEBHOOKS_CONFIG_NAME => $webhooks
                ]
            ],
            true
        );

        return $hook;
    }

    /**
     * Removes the hook from the deployment configuration.
     *
     * Returns true if the hook was found and deleted, false otherwise.
     *
     * @inheritDoc
     */
    public function delete(HookInterface $hook): bool
    {
        $webhooks = $this->deploymentConfig->get(self::WEBHOOKS_CONFIG_NAME, []);

        $webhookName = $this->hookNameGenerator->generate($hook);
        if (!isset($webhooks[$webhookName][$hook->getBatchName()][$hook->getHookName()])) {
            return false;
        }

        unset($webhooks[$webhookName][$hook->getBatchName()][$hook->getHookName()]);
        if (empty($webhooks[$webhookName][$hook->getBatchName()])) {
            unset($webhooks[$webhookName][$hook->getBatchName()]);
        }
        if (empty($webhooks[$webhookName])) {
            unset($webhooks[$webhookName]);
        }

        $this->configWriter->saveConfig(
            [
                ConfigFilePool::APP_ENV => [
                    self::WEBHOOKS_CONFIG_NAME => $webhooks
                ]
            ],
            true
        );

        return true;
    }

    /**
     * Loads the hook by its ID from deployment configuration
     *
     * @param string $hookId
     * @return HookInterface
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function loadHook(string $hookId): HookInterface
    {
        [$webhookMethod, $webhookType, $batchName, $hookName] = array_pad(
            explode(HookIdGenerator::ID_DELIMITER, $hookId),
            4,
            ''
        );
        $webhooks = $this->deploymentConfig->get(self::WEBHOOKS_CONFIG_NAME, []);

        $webhookName = $this->webhookNameGenerator->generate($webhookMethod, $webhookType);
        if (isset($webhooks[$webhookName][$batchName][$hookName])) {
            $hookData = $webhooks[$webhookName][$batchName][$hookName];

            return $this->hookFactory->create([
                'data' => [
                    'id' => $hookId,
                    HookInterface::FIELD_WEBHOOK_METHOD => $webhookMethod,
                    HookInterface::FIELD_WEBHOOK_TYPE => $webhookType,
                    HookInterface::FIELD_BATCH_NAME => $batchName,
                    HookInterface::FIELD_HOOK_NAME => $hookName,
                    HookInterface::FIELD_HOOK_DATA => $hookData
                ]
            ]);
        }

        return $this->hookFactory->create();
    }
}
