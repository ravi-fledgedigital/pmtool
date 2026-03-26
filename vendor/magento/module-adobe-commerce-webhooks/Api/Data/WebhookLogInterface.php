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

namespace Magento\AdobeCommerceWebhooks\Api\Data;

/**
 * Defines the webhooks logs database model
 */
interface WebhookLogInterface
{
    public const FIELD_ID = 'id';
    public const FIELD_LEVEL = 'level';
    public const FIELD_WEBHOOK_TYPE = 'webhook_type';
    public const FIELD_WEBHOOK_METHOD = 'webhook_method';
    public const FIELD_BATCH_NAME = 'batch_name';
    public const FIELD_HOOK_NAME = 'hook_name';
    public const FIELD_REQUEST_ID = 'request_id';
    public const FIELD_MESSAGE = 'message';
    public const FIELD_CREATED_AT = 'created_at';

    /**
     * Returns webhooks log id.
     *
     * @return string|null
     */
    public function getId(): ?string;

    /**
     * Returns method for the webhooks log record.
     *
     * @return string
     */
    public function getWebhookMethod(): string;

    /**
     * Returns type name for the webhooks log record.
     *
     * @return string
     */
    public function getWebhookType(): string;

    /**
     * Returns batch name for the webhooks log record.
     *
     * @return string
     */
    public function getBatchName(): string;

    /**
     * Returns hook name for the webhooks log record.
     *
     * @return string
     */
    public function getHookName(): string;

    /**
     * Returns webhooks log message.
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * Returns creation time for the webhooks log record.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;
}
