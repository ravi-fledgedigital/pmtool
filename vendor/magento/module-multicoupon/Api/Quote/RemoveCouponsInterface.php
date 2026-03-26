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

namespace Magento\Multicoupon\Api\Quote;

/**
 * Interface to remove specific quote coupons from a cart.
 */
interface RemoveCouponsInterface
{
    /**
     * Remove coupons by code associated to a cart.
     *
     * @param string $cartId The cart ID.
     * @param string[] $couponCodes Multiple coupons code data.
     * @return void
     */
    public function execute(string $cartId, array $couponCodes): void;
}
