<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained from
 * Adobe.
 */
declare(strict_types=1);

namespace Magento\Multicoupon\Plugin\SalesRule;

use Magento\Multicoupon\Model\Config\Config;
use Magento\Quote\Model\Quote\Address;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\SelectRuleCoupon;
use Magento\SalesRule\Model\ValidateCoupon;

class ValidateCouponPlugin
{
    /**
     * @param Config $config
     * @param SelectRuleCoupon $selectRuleCoupon
     */
    public function __construct(
        private readonly Config $config,
        private readonly SelectRuleCoupon $selectRuleCoupon
    ) {
    }

    /**
     * Pass corresponding coupon code for the rule
     *
     * @param ValidateCoupon $subject
     * @param Rule $rule
     * @param Address $address
     * @param string|null $couponCode
     * @return array
     */
    public function beforeExecute(ValidateCoupon $subject, Rule $rule, Address $address, ?string $couponCode): array
    {
        if ($this->config->getMaximumNumberOfCoupons() <= 1) {
            return [$rule, $address, $couponCode];
        }
        if ($rule->getCouponType() == Rule::COUPON_TYPE_NO_COUPON) {
            return [$rule, $address, $couponCode];
        }
        $coupons = $address->getQuote()->getExtensionAttributes()->getCouponCodes();
        if (empty($coupons) || count($coupons) <= 1) {
            return [$rule, $address, $couponCode];
        }
        $coupon = $this->selectRuleCoupon->execute($rule, $coupons);
        return [$rule, $address, $coupon];
    }
}
