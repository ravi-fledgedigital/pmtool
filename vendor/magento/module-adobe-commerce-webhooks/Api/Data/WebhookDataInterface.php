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

namespace Magento\AdobeCommerceWebhooks\Api\Data;

use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;

/**
 * Interface for webhook data from webapi requests
 *
 * @api
 */
interface WebhookDataInterface
{
    public const WEBHOOK_METHOD = 'webhook_method';
    public const WEBHOOK_TYPE = 'webhook_type';
    public const BATCH_NAME = 'batch_name';
    public const BATCH_ORDER = 'batch_order';
    public const HOOK_NAME = 'hook_name';
    public const URL = Hook::URL;
    public const PRIORITY = Hook::PRIORITY;
    public const REQUIRED = Hook::REQUIRED;
    public const SOFT_TIMEOUT = Hook::SOFT_TIMEOUT;
    public const TIMEOUT = Hook::TIMEOUT;
    public const METHOD = Hook::METHOD;
    public const FALLBACK_ERROR_MESSAGE = Hook::FALLBACK_ERROR_MESSAGE;
    public const TTL = Hook::TTL;
    public const FIELDS = Hook::FIELDS;
    public const RULES = Hook::RULES;
    public const HEADERS = Hook::HEADERS;
    public const DEVELOPER_CONSOLE_OAUTH = 'developer_console_oauth';

    /**
     * Sets webhook method name
     *
     * @param string $webhookMethod
     * @return WebhookDataInterface
     */
    public function setWebhookMethod(string $webhookMethod): WebhookDataInterface;

    /**
     * Returns webhook method name
     *
     * @return string
     */
    public function getWebhookMethod(): string;

    /**
     * Sets webhook type
     *
     * @param string $webhookType
     * @return WebhookDataInterface
     */
    public function setWebhookType(string $webhookType): WebhookDataInterface;

    /**
     * Returns webhook type
     *
     * @return string
     */
    public function getWebhookType(): string;

    /**
     * Sets webhook batch name
     *
     * @param string $batchName
     * @return WebhookDataInterface
     */
    public function setBatchName(string $batchName): WebhookDataInterface;

    /**
     * Returns webhook batch name
     *
     * @return string
     */
    public function getBatchName(): string;

    /**
     * Sets webhook batch order
     *
     * @param int $batchOrder
     * @return WebhookDataInterface
     */
    public function setBatchOrder(int $batchOrder): WebhookDataInterface;

    /**
     * Returns webhook batch order
     *
     * @return int
     */
    public function getBatchOrder(): int;

    /**
     * Sets hook name
     *
     * @param string $hookName
     * @return WebhookDataInterface
     */
    public function setHookName(string $hookName): WebhookDataInterface;

    /**
     * Returns hook name
     *
     * @return string
     */
    public function getHookName(): string;

    /**
     * Sets hook url
     *
     * @param string $url
     * @return WebhookDataInterface
     */
    public function setUrl(string $url): WebhookDataInterface;

    /**
     * Returns hook url
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Sets hook priority
     *
     * @param int $priority
     * @return WebhookDataInterface
     */
    public function setPriority(int $priority): WebhookDataInterface;

    /**
     * Returns hook priority
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Sets hook required
     *
     * @param bool $required
     * @return WebhookDataInterface
     */
    public function setRequired(bool $required): WebhookDataInterface;

    /**
     * Returns if hook is required
     *
     * @return bool
     */
    public function isRequired(): bool;

    /**
     * Sets hook soft timeout
     *
     * @param int $softTimeout
     * @return WebhookDataInterface
     */
    public function setSoftTimeout(int $softTimeout): WebhookDataInterface;

    /**
     * Returns hook soft timeout
     *
     * @return int
     */
    public function getSoftTimeout(): int;

    /**
     * Sets hook timeout
     *
     * @param int $timeout
     * @return WebhookDataInterface
     */
    public function setTimeout(int $timeout): WebhookDataInterface;

    /**
     * Returns hook timeout
     *
     * @return int
     */
    public function getTimeout(): int;

    /**
     * Sets hook HTTP method.
     *
     * The method is the HTTP method to use when sending the webhook. The default is POST.
     *
     * @param string $method
     * @return WebhookDataInterface
     */
    public function setMethod(string $method): WebhookDataInterface;

    /**
     * Returns hook method
     *
     * @return string
     */
    public function getMethod(): string;

    /**
     * Sets hook fallback error message
     *
     * @param string $fallbackErrorMessage
     * @return WebhookDataInterface
     */
    public function setFallbackErrorMessage(string $fallbackErrorMessage): WebhookDataInterface;

    /**
     * Returns hook fallback error message
     *
     * @return string
     */
    public function getFallbackErrorMessage(): string;

    /**
     * Sets hook ttl
     *
     * @param int $ttl
     * @return WebhookDataInterface
     */
    public function setTtl(int $ttl): WebhookDataInterface;

    /**
     * Returns hook ttl
     *
     * @return int
     */
    public function getTtl(): int;

    /**
     * Sets webhook fields
     *
     * @param \Magento\AdobeCommerceWebhooks\Api\Data\HookFieldInterface[] $fields
     * @return WebhookDataInterface
     */
    public function setFields(array $fields): WebhookDataInterface;

    /**
     * Returns webhook fields
     *
     * @return \Magento\AdobeCommerceWebhooks\Api\Data\HookFieldInterface[]
     */
    public function getFields(): array;

    /**
     * Sets webhook rules
     *
     * @param \Magento\AdobeCommerceWebhooks\Api\Data\HookRuleInterface[] $rules
     * @return WebhookDataInterface
     */
    public function setRules(array $rules): WebhookDataInterface;

    /**
     * Returns webhook rules
     *
     * @return \Magento\AdobeCommerceWebhooks\Api\Data\HookRuleInterface[]
     */
    public function getRules(): array;

    /**
     * Sets webhook headers
     *
     * @param \Magento\AdobeCommerceWebhooks\Api\Data\HookHeaderInterface[] $headers
     * @return WebhookDataInterface
     */
    public function setHeaders(array $headers): WebhookDataInterface;

    /**
     * Returns webhook headers
     *
     * @return \Magento\AdobeCommerceWebhooks\Api\Data\HookHeaderInterface[]
     */
    public function getHeaders(): array;

    /**
     * Gets the Developer Console OAuth data.
     *
     * @return \Magento\AdobeCommerceWebhooks\Api\Data\DeveloperConsoleOauthInterface|null
     */
    public function getDeveloperConsoleOauth(): ?DeveloperConsoleOauthInterface;

    /**
     * Sets the Developer Console OAuth data.
     *
     * @param \Magento\AdobeCommerceWebhooks\Api\Data\DeveloperConsoleOauthInterface $developerConsoleOauth
     * @return \Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface
     */
    public function setDeveloperConsoleOauth(
        DeveloperConsoleOauthInterface $developerConsoleOauth
    ): WebhookDataInterface;
}
