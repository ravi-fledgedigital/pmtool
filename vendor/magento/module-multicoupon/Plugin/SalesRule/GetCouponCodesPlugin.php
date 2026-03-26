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

use Magento\Quote\Api\Data\CartInterface;
use Magento\SalesRule\Model\Quote\GetCouponCodes;

class GetCouponCodesPlugin
{
    /**
     * Include multiple coupons when retrieving coupons from the quote
     *
     * @param GetCouponCodes $subject
     * @param string[] $result
     * @param CartInterface $quote
     * @return array
     */
    public function afterExecute(GetCouponCodes $subject, array $result, CartInterface $quote): array
    {
        return array_filter(
            array_unique(
                array_merge(
                    $result,
                    $quote->getExtensionAttributes()->getCouponCodes() ?? []
                )
            )
        );
    }
}
