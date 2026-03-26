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

namespace Magento\MulticouponGraphQl\Plugin\Model\Resolver;

use \Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Multicoupon\Api\CouponManagementInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\QuoteGraphQl\Model\Resolver\AppliedCoupons;

class MultipleAppliedCoupons
{
    /**
     * @param CouponManagementInterface $couponManagement
     */
    public function __construct(
        private readonly CouponManagementInterface $couponManagement
    ) {
    }

    /**
     * Retrieves the coupon codes associated to a specific quote.
     *
     * @param AppliedCoupons $subject
     * @param array|null $result
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|null
     * @throws LocalizedException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterResolve(
        AppliedCoupons $subject,
        array|null $result,
        Field $field,
        ContextInterface $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array|null {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        /** @var CartInterface $cart */
        $cart = $value['model'];

        $couponCodes = $cart->getExtensionAttributes()->getCouponCodes() ?? [];

        $multipleCouponsResult = [];
        if ($couponCodes) {
            $multipleCouponsResult = array_map(function ($couponCode) {
                 return ['code' => $couponCode];
            }, $couponCodes);
        }

        if (!empty($result) && isset(reset($result)['code']) && !in_array(reset($result)['code'], $couponCodes)) {
            return array_merge($result, $multipleCouponsResult);
        }

        return !empty($multipleCouponsResult) ? $multipleCouponsResult : null;
    }
}
