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

namespace Magento\AdobeCommerceWebhooksSubscriber\Api\Data;

use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface;
use Magento\AdobeCommerceWebhooks\Model\Config\WebhookConfigurationException;

/**
 * Defines the hook model
 */
interface HookInterface
{
    public const FIELD_ID = 'id';
    public const FIELD_WEBHOOK_TYPE = WebhookDataInterface::WEBHOOK_TYPE;
    public const FIELD_WEBHOOK_METHOD = WebhookDataInterface::WEBHOOK_METHOD;
    public const FIELD_BATCH_NAME = WebhookDataInterface::BATCH_NAME;
    public const FIELD_BATCH_ORDER = WebhookDataInterface::BATCH_ORDER;
    public const FIELD_HOOK_NAME = WebhookDataInterface::HOOK_NAME;
    public const FIELD_HOOK_DATA = 'hook_data';

    /**
     * Returns hook id.
     *
     * @return string|null
     */
    public function getId(): ?string;

    /**
     * Returns method for the hook's associated webhook.
     *
     * @return string
     */
    public function getWebhookMethod(): string;

    /**
     * Returns type for the hook's associated webhook.
     *
     * @return string
     */
    public function getWebhookType(): string;

    /**
     * Returns name for the hook's associated batch.
     *
     * @return string
     */
    public function getBatchName(): string;

    /**
     * Returns the order for the hook's associated batch.
     *
     * @return int
     */
    public function getBatchOrder(): int;

    /**
     * Sets the batch order for the hook's associated batch.
     *
     * @param int $batchOrder
     * @return HookInterface
     */
    public function setBatchOrder(int $batchOrder): HookInterface;

    /**
     * Returns hook name.
     *
     * @return string
     */
    public function getHookName(): string;

    /**
     * Returns hook configuration.
     *
     * @return array
     * @throws WebhookConfigurationException
     */
    public function getHookData(): array;
}
