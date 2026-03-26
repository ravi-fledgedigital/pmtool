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

namespace Magento\Multicoupon\Model\SalesOrder;

use Magento\Multicoupon\Api\SalesOrder\ReplaceCouponsInterface;
use Magento\Multicoupon\Model\ResourceModel\SalesOrder\AddCoupons;
use Magento\Multicoupon\Model\ResourceModel\SalesOrder\RemoveAllCoupons;

class ReplaceCoupons implements ReplaceCouponsInterface
{
    /**
     * Constructs a ReplaceCoupons service object.
     *
     * @param RemoveAllCoupons $removeAllCoupons
     * @param AddCoupons $addCoupons
     */
    public function __construct(
        private RemoveAllCoupons $removeAllCoupons,
        private AddCoupons $addCoupons
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(string $orderId, array $couponDiscounts = []): void
    {
        $this->removeAllCoupons->execute($orderId);
        $this->addCoupons->execute($orderId, $couponDiscounts);
    }
}
