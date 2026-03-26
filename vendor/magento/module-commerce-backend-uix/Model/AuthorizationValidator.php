<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Model;

use Magento\Framework\AuthorizationInterface;

/**
 * Admin UI SDK Authorization class to check is user is authorized to view and use registrations
 */
class AuthorizationValidator
{
    private const ADMIN_RESOURCE = 'Magento_CommerceBackendUix::admin';

    /**
     * @param Config $config
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        private Config $config,
        private AuthorizationInterface $authorization
    ) {
    }

    /**
     * Checks if the Admin UI SDK is enabled and allowed
     *
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return $this->config->isAdminUISDKEnabled() && $this->authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
