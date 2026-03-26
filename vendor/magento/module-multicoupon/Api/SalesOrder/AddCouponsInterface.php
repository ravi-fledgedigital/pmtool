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

namespace Magento\Multicoupon\Api\SalesOrder;

/**
 * Interface to add multiple coupons to a sales order.
 */
interface AddCouponsInterface
{
    /**
     * Adds coupons by code to specific order.
     *
     * @param string $orderId The order ID.
     * @param array $couponDiscounts Multiple coupons code and discount data.
     * @return void
     */
    public function execute(string $orderId, array $couponDiscounts): void;
}
