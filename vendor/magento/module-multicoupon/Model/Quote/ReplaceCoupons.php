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

namespace Magento\Multicoupon\Model\Quote;

use Magento\Multicoupon\Api\Quote\ReplaceCouponsInterface;
use Magento\Multicoupon\Model\ResourceModel\Quote\AddCoupons;
use Magento\Multicoupon\Model\ResourceModel\Quote\RemoveAllCoupons;

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
    public function execute(string $cartId, array $couponCodes = []): void
    {
        $this->removeAllCoupons->execute($cartId);
        $this->addCoupons->execute($cartId, $couponCodes);
    }
}
