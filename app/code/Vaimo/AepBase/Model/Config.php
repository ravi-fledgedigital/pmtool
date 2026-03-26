<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepBase\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Vaimo\AepBase\Api\ConfigInterface;

class Config implements ConfigInterface
{
    private const XPATH_ENABLED = 'aep/general/enabled';
    private const XPATH_PRIVATE_KEY = 'aep/general/private_key';
    private const XPATH_CUSTOMER_ID_PREFIX = 'aep/general/customer_id_prefix';

    private const XPATH_DATA_AGGREGATION_ENABLED = 'aep/data_aggregation/enabled';

    // phpcs:ignore Vaimo.Classes.ProtectedMember.Property
    protected ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XPATH_ENABLED);
    }

    public function isDataAggregationEnabled(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(self::XPATH_DATA_AGGREGATION_ENABLED);
    }

    public function getPrivateKey(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_PRIVATE_KEY);
    }

    public function getCustomerIdPrefix(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_CUSTOMER_ID_PREFIX);
    }
}
