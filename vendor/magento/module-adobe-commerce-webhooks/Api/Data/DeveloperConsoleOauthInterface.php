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

use Magento\AdobeCommerceWebhooks\Model\Ims\CredentialsInterface;

/**
 * Interface for Developer Console OAuth data from webapi requests.
 */
interface DeveloperConsoleOauthInterface
{
    public const CLIENT_ID = CredentialsInterface::CLIENT_ID;
    public const CLIENT_SECRET = CredentialsInterface::CLIENT_SECRET;
    public const ORG_ID = CredentialsInterface::ORG_ID;
    public const ENVIRONMENT = CredentialsInterface::ENVIRONMENT;
    public const ENABLED = 'enabled';

    /**
     * Constants for Developer Console OAuth hooks fields.
     */
    public const DC_OAUTH_ENABLED = 'developer_console_oauth_enabled';
    public const DC_OAUTH_CLIENT_ID = 'developer_console_oauth_client_id';
    public const DC_OAUTH_CLIENT_SECRET = 'developer_console_oauth_client_secret';
    public const DC_OAUTH_ORG_ID = 'developer_console_oauth_org_id';
    public const DC_OAUTH_ENVIRONMENT = 'developer_console_oauth_environment';

    /**
     * Sets the Developer Console OAuth client ID.
     *
     * @param string $clientId
     * @return DeveloperConsoleOauthInterface
     */
    public function setClientId(string $clientId): DeveloperConsoleOauthInterface;

    /**
     * Gets the Developer Console OAuth client ID.
     *
     * @return string
     */
    public function getClientId(): string;

    /**
     * Sets the Developer Console OAuth client secret.
     *
     * @param string $clientSecret
     * @return DeveloperConsoleOauthInterface
     */
    public function setClientSecret(string $clientSecret): DeveloperConsoleOauthInterface;

    /**
     * Gets the Developer Console OAuth client secret.
     *
     * @return string
     */
    public function getClientSecret(): string;

    /**
     * Sets the Developer Console OAuth org ID.
     *
     * @param string $orgId
     * @return DeveloperConsoleOauthInterface
     */
    public function setOrgId(string $orgId): DeveloperConsoleOauthInterface;

    /**
     * Gets the Developer Console OAuth org ID.
     *
     * @return string
     */
    public function getOrgId(): string;

    /**
     * Sets the Developer Console OAuth environment.
     *
     * @param string $environment
     * @return DeveloperConsoleOauthInterface
     */
    public function setEnvironment(string $environment): DeveloperConsoleOauthInterface;

    /**
     * Gets the Developer Console OAuth environment.
     *
     * @return string
     */
    public function getEnvironment(): string;
}
