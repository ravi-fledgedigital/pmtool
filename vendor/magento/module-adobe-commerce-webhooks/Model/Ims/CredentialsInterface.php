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

namespace Magento\AdobeCommerceWebhooks\Model\Ims;

/**
 * Credentials to access developer console actions
 */
interface CredentialsInterface
{
    public const CLIENT_ID = 'client_id';
    public const CLIENT_SECRET = 'client_secret';
    public const ORG_ID = 'org_id';
    public const SCOPES = 'scopes';
    public const ENVIRONMENT = 'environment';

    public const ENVIRONMENT_PRODUCTION = 'production';
    public const ENVIRONMENT_STAGING = 'staging';

    /**
     * Returns the client ID.
     *
     * @return string
     */
    public function getClientId(): string;

    /**
     * Returns the client secret.
     *
     * @return string
     */
    public function getClientSecret(): string;

    /**
     * Returns the organization ID.
     *
     * @return string
     */
    public function getOrgId(): string;

    /**
     * Returns the scopes.
     *
     * @return string
     */
    public function getScopes(): string;

    /**
     * Returns the environment type.
     *
     * @return string
     */
    public function getEnvironment(): string;
}
