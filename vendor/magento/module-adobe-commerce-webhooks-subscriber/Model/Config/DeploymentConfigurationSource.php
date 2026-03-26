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

namespace Magento\AdobeCommerceWebhooksSubscriber\Model\Config;

use Exception;
use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface;
use Magento\AdobeCommerceWebhooks\Model\Config\WebhookConfigurationSourceInterface;
use Magento\AdobeCommerceWebhooks\Model\Webhook;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Batch;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooksSubscriber\Model\HookRepository;
use Magento\Framework\App\DeploymentConfig;

class DeploymentConfigurationSource implements WebhookConfigurationSourceInterface
{
    /**
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(private readonly DeploymentConfig $deploymentConfig)
    {
    }

    /**
     * Returns webhooks configuration from deployment configuration.
     *
     * @return array
     */
    public function getConfig(): array
    {
        try {
            $webhooksConfig = $this->deploymentConfig->get(HookRepository::WEBHOOKS_CONFIG_NAME);
        } catch (Exception $e) {
            return [];
        }

        if (!is_array($webhooksConfig)) {
            return [];
        }

        $config = [];

        foreach ($webhooksConfig as $webhookName => $batches) {
            foreach ($batches as $batchName => $hooks) {
                foreach ($hooks as $hookName => $hookData) {
                    if (empty($hookData)) {
                        continue;
                    }

                    [$webhookMethod, $webhookType] = explode(':', $webhookName, 2);
                    if (!isset($config[$webhookName])) {
                        $config[$webhookName] = [
                            Webhook::NAME => $webhookMethod,
                            Webhook::TYPE => $webhookType,
                        ];
                    }

                    $config[$webhookName][Webhook::BATCHES] = $this->addHookToBatches(
                        $config[$webhookName][Webhook::BATCHES] ?? [],
                        $batchName,
                        $hookName,
                        $hookData[WebhookDataInterface::BATCH_ORDER] ?? 0,
                        $hookData
                    );
                }
            }
        }

        return $config;
    }

    /**
     * Adds the input hook data to the input batch configuration for a webhook.
     *
     * @param array $batches
     * @param string $batchName
     * @param string $hookName
     * @param int $batchOrder
     * @param array $hookData
     * @return array
     */
    private function addHookToBatches(
        array $batches,
        string $batchName,
        string $hookName,
        int $batchOrder,
        array $hookData
    ): array {
        $hookData[Hook::NAME] = $hookName;
        if (isset($batches[$batchName])) {
            $batches[$batchName][Batch::HOOKS][$hookName] = $hookData;
        } else {
            $batches[$batchName] = [
                Batch::NAME => $batchName,
                Batch::ORDER => $batchOrder,
                Batch::HOOKS => [
                    $hookName => $hookData
                ]
            ];
        }
        return $batches;
    }
}
