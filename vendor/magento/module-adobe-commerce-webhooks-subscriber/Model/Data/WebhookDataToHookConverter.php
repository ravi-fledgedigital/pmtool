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

namespace Magento\AdobeCommerceWebhooksSubscriber\Model\Data;

use Magento\AdobeCommerceWebhooks\Api\Data\DeveloperConsoleOauthInterface;
use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface;
use Magento\AdobeCommerceWebhooks\Model\Config\RuleNameGenerator;
use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterface;
use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterfaceFactory;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Converts webhook data to hook object
 */
class WebhookDataToHookConverter
{
    /**
     * @param HookInterfaceFactory $hookFactory
     * @param RuleNameGenerator $ruleNameGenerator
     * @param Json $json
     * @param Encryptor $encryptor
     */
    public function __construct(
        private readonly HookInterfaceFactory $hookFactory,
        private readonly RuleNameGenerator $ruleNameGenerator,
        private readonly Json $json,
        private readonly Encryptor $encryptor
    ) {
    }

    /**
     * Converts webhook data to hook object
     *
     * @param WebhookDataInterface $webhookData
     * @return HookInterface
     */
    public function convert(WebhookDataInterface $webhookData): HookInterface
    {
        return $this->hookFactory->create([
            'data' => [
                HookInterface::FIELD_WEBHOOK_TYPE => $webhookData->getWebhookType(),
                HookInterface::FIELD_WEBHOOK_METHOD => $webhookData->getWebhookMethod(),
                HookInterface::FIELD_BATCH_NAME => $webhookData->getBatchName(),
                HookInterface::FIELD_BATCH_ORDER => $webhookData->getBatchOrder(),
                HookInterface::FIELD_HOOK_NAME => $webhookData->getHookName(),
                HookInterface::FIELD_HOOK_DATA => $this->json->serialize($this->createHookData($webhookData))
            ]
        ]);
    }

    /**
     * Creates hook data based on webhook data
     *
     * @param WebhookDataInterface $webhookData
     * @return array
     */
    private function createHookData(WebhookDataInterface $webhookData): array
    {
        $result = [
            WebhookDataInterface::TTL => $webhookData->getTtl(),
            WebhookDataInterface::URL => trim($webhookData->getUrl()),
            WebhookDataInterface::PRIORITY => $webhookData->getPriority(),
            WebhookDataInterface::SOFT_TIMEOUT => $webhookData->getSoftTimeout(),
            WebhookDataInterface::TIMEOUT => $webhookData->getTimeout(),
            WebhookDataInterface::METHOD => $webhookData->getMethod(),
            WebhookDataInterface::FALLBACK_ERROR_MESSAGE => $webhookData->getFallbackErrorMessage(),
            WebhookDataInterface::REQUIRED => $webhookData->isRequired(),
        ];

        foreach ($webhookData->getFields() as $field) {
            $result[WebhookDataInterface::FIELDS][$field->getName()] = $field->getData();
        }
        foreach ($webhookData->getHeaders() as $header) {
            $result[WebhookDataInterface::HEADERS][$header->getName()] = $header->getData();
        }
        foreach ($webhookData->getRules() as $rule) {
            $key = $this->ruleNameGenerator->generate($rule->getField(), $rule->getOperator());
            $result[WebhookDataInterface::RULES][$key] = $rule->getData();
        }

        return array_merge($result, $this->createDeveloperConsoleOauthData($webhookData));
    }

    /**
     * Creates a Developer Console OAuth data array from the webhook data
     *
     * @param WebhookDataInterface $webhookData
     * @return array
     */
    private function createDeveloperConsoleOauthData(WebhookDataInterface $webhookData): array
    {
        $developerConsoleOAuthData = $webhookData->getDeveloperConsoleOauth();
        if ($developerConsoleOAuthData === null) {
            return [];
        }
        return [
            DeveloperConsoleOauthInterface::DC_OAUTH_ENABLED => 'true',
            DeveloperConsoleOauthInterface::DC_OAUTH_CLIENT_ID => $developerConsoleOAuthData->getClientId(),
            DeveloperConsoleOauthInterface::DC_OAUTH_CLIENT_SECRET => $developerConsoleOAuthData->getClientSecret() ?
                $this->encryptor->encrypt($developerConsoleOAuthData->getClientSecret()) : '',
            DeveloperConsoleOauthInterface::DC_OAUTH_ORG_ID => $developerConsoleOAuthData->getOrgId(),
            DeveloperConsoleOauthInterface::DC_OAUTH_ENVIRONMENT =>
                $developerConsoleOAuthData->getEnvironment() ?: 'production',
        ];
    }
}
