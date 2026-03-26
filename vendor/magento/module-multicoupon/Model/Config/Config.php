<?php
/************************************************************************
 *
 * Copyright 2023 Adobe
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

namespace Magento\Multicoupon\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;

/**
 * Config Model for multicoupon
 */
class Config
{
    private const SALES_MULTICOUPON_MAXIMUM_COUPONS = 'sales/multicoupon/maximum_number_of_coupons_per_order';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Returns maximum number of coupons per order
     *
     * @return mixed
     */
    public function getMaximumNumberOfCoupons()
    {
        return (int) $this->scopeConfig->getValue(
            self::SALES_MULTICOUPON_MAXIMUM_COUPONS,
            StoreScopeInterface::SCOPE_STORE
        );
    }
}
