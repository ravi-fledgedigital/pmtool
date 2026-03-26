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

namespace Magento\AdobeCommerceWebhooks\Model\Config\System;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * System configuration for webhooks.
 */
class Config
{
    public const CONFIG_PATH_SIGNATURE_ENABLED = 'commerce_webhooks/digital_signature/enabled';
    public const CONFIG_PATH_SIGNATURE_PUBLIC_KEY = 'commerce_webhooks/digital_signature/public_key';
    public const CONFIG_PATH_SIGNATURE_PRIVATE_KEY = 'commerce_webhooks/digital_signature/private_key';

    public const CONFIG_PATH_DB_LOG_ENABLED = 'commerce_webhooks/db_log/db_log_enabled';
    public const CONFIG_PATH_DB_LOG_RETENTION_PERIOD = 'commerce_webhooks/db_log/retention_period';
    public const CONFIG_PATH_DB_LOG_LEVEL = 'commerce_webhooks/db_log/level';
    public const CONFIG_PATH_DB_LOG_FULL_MESSAGE = 'commerce_webhooks/db_log/db_log_full_message';

    /**
     * @param ScopeConfigInterface $config
     */
    public function __construct(private ScopeConfigInterface $config)
    {
    }

    /**
     * Checks if webhooks digital signature is enabled.
     *
     * @return bool
     */
    public function isDigitalSignatureEnabled(): bool
    {
        return (bool)$this->config->getValue(self::CONFIG_PATH_SIGNATURE_ENABLED);
    }

    /**
     * Returns digital signature public key.
     *
     * @return string
     */
    public function getDigitalSignaturePublicKey(): string
    {
        return (string)$this->config->getValue(self::CONFIG_PATH_SIGNATURE_PUBLIC_KEY);
    }

    /**
     * Returns digital signature private key.
     *
     * @return string
     */
    public function getDigitalSignaturePrivateKey(): string
    {
        return (string)$this->config->getValue(self::CONFIG_PATH_SIGNATURE_PRIVATE_KEY);
    }

    /**
     * Checks if the webhooks database log enabled in the settings.
     *
     * @return bool
     */
    public function isDbLogEnabled(): bool
    {
        return (bool)$this->config->getValue(self::CONFIG_PATH_DB_LOG_ENABLED);
    }

    /**
     * Returns retention period in days for webhooks database logs.
     *
     * @return int
     */
    public function getDbLogRetentionPeriod(): int
    {
        return (int)$this->config->getValue(self::CONFIG_PATH_DB_LOG_RETENTION_PERIOD);
    }

    /**
     * Returns log level for webhooks database logs.
     *
     * @return string
     */
    public function getDbLogLevel(): string
    {
        return (string)$this->config->getValue(self::CONFIG_PATH_DB_LOG_LEVEL);
    }

    /**
     * Checks if saving of the full log message in the database is enabled.
     *
     * @return bool
     */
    public function isFullLogMessageEnabled(): bool
    {
        return (bool)$this->config->getValue(self::CONFIG_PATH_DB_LOG_FULL_MESSAGE);
    }
}
