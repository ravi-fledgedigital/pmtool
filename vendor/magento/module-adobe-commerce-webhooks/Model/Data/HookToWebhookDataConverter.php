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

namespace Magento\AdobeCommerceWebhooks\Model\Data;

use Magento\AdobeCommerceWebhooks\Api\Data\DeveloperConsoleOauthInterface;
use Magento\AdobeCommerceWebhooks\Api\Data\DeveloperConsoleOauthInterfaceFactory;
use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface;
use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterfaceFactory;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookField;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookRule;

/**
 * Converts hook object to webhook data.
 */
class HookToWebhookDataConverter
{
    /**
     * @param WebhookDataInterfaceFactory $webhookDataFactory
     * @param DeveloperConsoleOauthInterfaceFactory $developerConsoleOauthFactory
     */
    public function __construct(
        private readonly WebhookDataInterfaceFactory $webhookDataFactory,
        private readonly DeveloperConsoleOauthInterfaceFactory $developerConsoleOauthFactory,
    ) {
    }

    /**
     * Converts hook object to webhook data.
     *
     * @param Hook $hook
     * @return WebhookDataInterface
     */
    public function convert(Hook $hook): WebhookDataInterface
    {
        $batch = $hook->getBatch();

        return $this->webhookDataFactory->create(['data' => [
            WebhookDataInterface::WEBHOOK_METHOD => $batch->getWebhook()->getName(),
            WebhookDataInterface::WEBHOOK_TYPE => $batch->getWebhook()->getType(),
            WebhookDataInterface::BATCH_NAME => $batch->getName(),
            WebhookDataInterface::BATCH_ORDER => $batch->getOrder(),
            WebhookDataInterface::HOOK_NAME => $hook->getName(),
            WebhookDataInterface::URL => $hook->getUrl(),
            WebhookDataInterface::PRIORITY => $hook->getPriority(),
            WebhookDataInterface::REQUIRED => $hook->isRequired(),
            WebhookDataInterface::SOFT_TIMEOUT => $hook->getSoftTimeout(),
            WebhookDataInterface::TIMEOUT => $hook->getTimeout(),
            WebhookDataInterface::METHOD => $hook->getMethod(),
            WebhookDataInterface::TTL => $hook->getTtl(),
            WebhookDataInterface::FALLBACK_ERROR_MESSAGE => $hook->getFallbackErrorMessage(),
            WebhookDataInterface::FIELDS => $this->extractHookAttributes(
                $hook->getFields(),
                [HookField::NAME, HookField::SOURCE],
                HookField::REMOVE
            ),
            WebhookDataInterface::RULES => $this->extractHookAttributes(
                $hook->getRules(),
                [HookRule::FIELD, HookRule::OPERATOR, HookRule::VALUE],
                HookRule::REMOVE
            ),
            WebhookDataInterface::HEADERS => $this->extractHookAttributes(
                $hook->getHeaders(),
                [HookHeader::NAME, HookHeader::VALUE],
                HookHeader::REMOVE
            ),
            WebhookDataInterface::DEVELOPER_CONSOLE_OAUTH => $this->getDeveloperConsoleOauth($hook),
        ]]);
    }

    /**
     * Hook entity attribute extractor (fields, rules, headers).
     *
     * @param array $hookEntities
     * @param array $attributeKeys
     * @param string $removeKey
     * @return array
     */
    private function extractHookAttributes(array $hookEntities, array $attributeKeys, string $removeKey): array
    {
        $attributes = [];

        foreach ($hookEntities as $entity) {
            $entityData = $entity->getData();
            if (isset($entityData[$removeKey]) && (string)$entityData[$removeKey] === 'true') {
                continue;
            }
            $attributes[] = array_intersect_key($entity->getData(), array_flip($attributeKeys));
        }

        return $attributes;
    }

    /**
     * Gets Developer Console OAuth data from the hook.
     *
     * @param Hook $hook
     * @return DeveloperConsoleOauthInterface|null
     */
    private function getDeveloperConsoleOauth(Hook $hook): ?DeveloperConsoleOauthInterface
    {
        $hookData = $hook->getData();
        if (!array_key_exists(DeveloperConsoleOauthInterface::DC_OAUTH_ENABLED, $hookData)) {
            return null;
        }
        $isEnabled = $hookData[DeveloperConsoleOauthInterface::DC_OAUTH_ENABLED];

        if ($isEnabled === 'true' || $isEnabled === true) {
            return $this->developerConsoleOauthFactory->create(['data' => [
                DeveloperConsoleOauthInterface::CLIENT_ID =>
                    $hookData[DeveloperConsoleOauthInterface::DC_OAUTH_CLIENT_ID] ?? '',
                DeveloperConsoleOauthInterface::CLIENT_SECRET =>
                    $hookData[DeveloperConsoleOauthInterface::DC_OAUTH_CLIENT_SECRET] ?? '',
                DeveloperConsoleOauthInterface::ORG_ID =>
                    $hookData[DeveloperConsoleOauthInterface::DC_OAUTH_ORG_ID] ?? '',
                DeveloperConsoleOauthInterface::ENVIRONMENT =>
                    $hookData[DeveloperConsoleOauthInterface::DC_OAUTH_ENVIRONMENT] ?? ''
            ]]);
        }

        return null;
    }
}
