<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTAdobeDataLayer\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Vaimo\OTAdobeDataLayer\Api\ConfigInterface;

class Config implements ConfigInterface
{
    private const XPATH_ADOBE_DATA_LAYER_ENABLED = 'adobe_launch/general/enabled';
    private const XPATH_LAUNCH_EMBED_CODE = 'adobe_launch/general/launch_embed_code';

    private const XPATH_USER_INFO_LOGGED_IN_SITE = 'adobe_launch/user_info/logged_in_site';
    private const XPATH_USER_INFO_LOGGED_IN_REGION = 'adobe_launch/user_info/logged_in_region';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XPATH_ADOBE_DATA_LAYER_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    public function getLaunchEmbedCode(): ?string
    {
        return $this->scopeConfig->getValue(self::XPATH_LAUNCH_EMBED_CODE, ScopeInterface::SCOPE_STORE);
    }

    public function getLoggedInSite(): ?string
    {
        return $this->scopeConfig->getValue(self::XPATH_USER_INFO_LOGGED_IN_SITE, ScopeInterface::SCOPE_STORE);
    }

    public function getLoggedInRegion(): ?string
    {
        return $this->scopeConfig->getValue(self::XPATH_USER_INFO_LOGGED_IN_REGION, ScopeInterface::SCOPE_STORE);
    }
}
