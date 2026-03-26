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

namespace Magento\Multicoupon\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class to set couponCode if only one coupon code is added
 */
class SetCouponCode
{
    /**
     * Set couponCode if only one coupon code is added
     *
     * @param CartInterface|OrderInterface $entity
     * @param array $couponCodes
     * @return void
     */
    public function execute(CartInterface|OrderInterface $entity, array $couponCodes): void
    {
        $entity->setCouponCode(count($couponCodes) === 1 ? reset($couponCodes) : '');
    }
}
