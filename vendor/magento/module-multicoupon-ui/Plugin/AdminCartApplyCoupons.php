<?php
/************************************************************************
 *
 * Copyright 2024 Adobe
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

namespace Magento\MulticouponUi\Plugin;

use Magento\AdvancedCheckout\Model\ApplyCoupons;
use Magento\Multicoupon\Api\CouponManagementInterface;
use Magento\Quote\Api\Data\CartInterface;

class AdminCartApplyCoupons
{
    /**
     * @param CouponManagementInterface $couponManagement
     */
    public function __construct(
        private readonly CouponManagementInterface $couponManagement
    ) {
    }

    /**
     * Apply multiple coupons
     *
     * @param ApplyCoupons $subject
     * @param CartInterface $quote
     * @param array $codes
     * @param bool $remove
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeApply(ApplyCoupons $subject, CartInterface $quote, array $codes, bool $remove = false): void
    {
        if ($remove) {
            $this->couponManagement->remove((int)$quote->getId(), $codes);
        } else {
            $this->couponManagement->append((int)$quote->getId(), $codes);
        }
    }
}
