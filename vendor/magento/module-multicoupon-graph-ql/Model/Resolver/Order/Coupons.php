<?php
/**
 * Copyright 2024 Adobe
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

namespace Magento\MulticouponGraphQl\Model\Resolver\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Coupons applied to the order
 */
class Coupons implements ResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!(($value['model'] ?? null) instanceof OrderInterface)) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        /** @var OrderInterface $parentOrder */
        $order = $value['model'];

        $coupons = $order->getExtensionAttributes()->getCouponCodes() ?? [];
        if ($order->getCouponCode() && !in_array($order->getCouponCode(), $coupons)) {
            array_unshift($coupons, $order->getCouponCode());
        }

        return array_map(
            function (string $coupon) {
                return ['code' => $coupon];
            },
            $coupons
        );
    }
}
