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
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\SalesRule\Model\ValidateCouponCode;
use Magento\SalesRule\Model\Validator;

class ValidatorPlugin
{
    /**
     * @param Config $config
     * @param ValidateCouponCode $validateCouponCode
     */
    public function __construct(
        private readonly Config $config,
        private readonly ValidateCouponCode $validateCouponCode
    ) {
    }

    /**
     * Set valid multiple coupon codes to the Validator/Calculator
     *
     * @param Validator $subject
     * @param Validator $result
     * @param CartInterface $quote
     * @return Validator
     */
    public function afterInitFromQuote(Validator $subject, Validator $result, CartInterface $quote): Validator
    {
        if ($this->config->getMaximumNumberOfCoupons() <= 1
            || empty($quote->getExtensionAttributes()->getCouponCodes())
        ) {
            return $result;
        }

        $validCoupons = $this->validateCouponCode->execute(
            $quote->getExtensionAttributes()->getCouponCodes() ?? [],
            $quote->getCustomerIsGuest() ? null : (int) $quote->getCustomer()->getId()
        );

        $firstCoupon = !empty($validCoupons) ? reset($validCoupons) : '';
        $quote->setCouponCode($firstCoupon);

        $quote->getExtensionAttributes()->setCouponCodes($validCoupons);
        $result->setCouponCodes($validCoupons);

        return $result;
    }
}
