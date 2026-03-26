<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7AsicsIntegration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigProvider
{
    public const SCHEDULER_ENABLED = 'catalog/scene7_sync/scheduler_enabled';
    public const BASE_URL_PATH = 'catalog/scene7_sync/base_url';
    public const REGION_SUFFIX = 'catalog/scene7_sync/region_suffix';
    public const AUTH_USER_PATH = 'catalog/scene7_sync/auth_user';
    public const AUTH_PASS_PATH = 'catalog/scene7_sync/auth_pass';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function isSchedulerEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::SCHEDULER_ENABLED);
    }

    public function getBaseUrl(): string
    {
        return \trim((string) $this->scopeConfig->getValue(self::BASE_URL_PATH), '/');
    }

    public function getRegionSuffix(): string
    {
        return (string) $this->scopeConfig->getValue(self::REGION_SUFFIX);
    }

    public function getAuthUser(): string
    {
        return (string) $this->scopeConfig->getValue(self::AUTH_USER_PATH);
    }

    public function getAuthPass(): string
    {
        return (string) $this->scopeConfig->getValue(self::AUTH_PASS_PATH);
    }
}
