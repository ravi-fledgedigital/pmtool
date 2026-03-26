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
use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface;
use Magento\Framework\DataObject;

/**
 * Webhook data for processing webapi requests.
 */
class WebhookData extends DataObject implements WebhookDataInterface
{
    /**
     * @inheritDoc
     */
    public function setWebhookMethod(string $webhookMethod): WebhookDataInterface
    {
        return $this->setData(self::WEBHOOK_METHOD, $webhookMethod);
    }

    /**
     * @inheritDoc
     */
    public function getWebhookMethod(): string
    {
        return (string)$this->getData(self::WEBHOOK_METHOD);
    }

    /**
     * @inheritDoc
     */
    public function setWebhookType(string $webhookType): WebhookDataInterface
    {
        return $this->setData(self::WEBHOOK_TYPE, $webhookType);
    }

    /**
     * @inheritDoc
     */
    public function getWebhookType(): string
    {
        return (string)$this->getData(self::WEBHOOK_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setBatchName(string $batchName): WebhookDataInterface
    {
        return $this->setData(self::BATCH_NAME, $batchName);
    }

    /**
     * @inheritDoc
     */
    public function getBatchName(): string
    {
        return (string)$this->getData(self::BATCH_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setBatchOrder(int $batchOrder): WebhookDataInterface
    {
        return $this->setData(self::BATCH_ORDER, $batchOrder);
    }

    /**
     * @inheritDoc
     */
    public function getBatchOrder(): int
    {
        return (int)$this->getData(self::BATCH_ORDER);
    }

    /**
     * @inheritDoc
     */
    public function setHookName(string $hookName): WebhookDataInterface
    {
        return $this->setData(self::HOOK_NAME, $hookName);
    }

    /**
     * @inheritDoc
     */
    public function getHookName(): string
    {
        return (string)$this->getData(self::HOOK_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setUrl(string $url): WebhookDataInterface
    {
        return $this->setData(self::URL, $url);
    }

    /**
     * @inheritDoc
     */
    public function getUrl(): string
    {
        return (string)$this->getData(self::URL);
    }

    /**
     * @inheritDoc
     */
    public function setPriority(int $priority): WebhookDataInterface
    {
        return $this->setData(self::PRIORITY, $priority);
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return (int)$this->getData(self::PRIORITY);
    }

    /**
     * @inheritDoc
     */
    public function setRequired(bool $required): WebhookDataInterface
    {
        return $this->setData(self::REQUIRED, $required);
    }

    /**
     * @inheritDoc
     */
    public function isRequired(): bool
    {
        return (bool)$this->getData(self::REQUIRED);
    }

    /**
     * @inheritDoc
     */
    public function setSoftTimeout(int $softTimeout): WebhookDataInterface
    {
        return $this->setData(self::SOFT_TIMEOUT, $softTimeout);
    }

    /**
     * @inheritDoc
     */
    public function getSoftTimeout(): int
    {
        return (int)$this->getData(self::SOFT_TIMEOUT);
    }

    /**
     * @inheritDoc
     */
    public function setTimeout(int $timeout): WebhookDataInterface
    {
        return $this->setData(self::TIMEOUT, $timeout);
    }

    /**
     * @inheritDoc
     */
    public function getTimeout(): int
    {
        return (int)$this->getData(self::TIMEOUT);
    }

    /**
     * @inheritDoc
     */
    public function setMethod(string $method): WebhookDataInterface
    {
        return $this->setData(self::METHOD, $method);
    }

    /**
     * @inheritDoc
     */
    public function getMethod(): string
    {
        return (string)$this->getData(self::METHOD);
    }

    /**
     * @inheritDoc
     */
    public function setFallbackErrorMessage(string $fallbackErrorMessage): WebhookDataInterface
    {
        return $this->setData(self::FALLBACK_ERROR_MESSAGE, $fallbackErrorMessage);
    }

    /**
     * @inheritDoc
     */
    public function getFallbackErrorMessage(): string
    {
        return (string)$this->getData(self::FALLBACK_ERROR_MESSAGE);
    }

    /**
     * @inheritDoc
     */
    public function setTtl(int $ttl): WebhookDataInterface
    {
        return $this->setData(self::TTL, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function getTtl(): int
    {
        return (int)$this->getData(self::TTL);
    }

    /**
     * @inheritDoc
     */
    public function setFields(array $fields): WebhookDataInterface
    {
        return $this->setData(self::FIELDS, $fields);
    }

    /**
     * @inheritDoc
     */
    public function getFields(): array
    {
        return $this->getData(self::FIELDS) ?: [];
    }

    /**
     * @inheritDoc
     */
    public function setRules(array $rules): WebhookDataInterface
    {
        return $this->setData(self::RULES, $rules);
    }

    /**
     * @inheritDoc
     */
    public function getRules(): array
    {
        return $this->getData(self::RULES) ?: [];
    }

    /**
     * @inheritDoc
     */
    public function setHeaders(array $headers): WebhookDataInterface
    {
        return $this->setData(self::HEADERS, $headers);
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): array
    {
        return $this->getData(self::HEADERS) ?: [];
    }

    /**
     * @inheritDoc
     */
    public function getDeveloperConsoleOauth(): ?DeveloperConsoleOauthInterface
    {
        return $this->getData(self::DEVELOPER_CONSOLE_OAUTH);
    }

    /**
     * @inheritDoc
     */
    public function setDeveloperConsoleOauth(
        DeveloperConsoleOauthInterface $developerConsoleOauth
    ): WebhookDataInterface {
        return $this->setData(self::DEVELOPER_CONSOLE_OAUTH, $developerConsoleOauth);
    }
}
