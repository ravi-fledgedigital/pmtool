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

namespace Magento\AdvancedCheckout\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;

class ApplyCoupons
{
    /**
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository
    ) {
    }

    /**
     * Apply coupon, recollect and save quote
     *
     * @param CartInterface $quote
     * @param array $codes
     * @param bool $remove
     * @return void
     * @throws LocalizedException
     */
    public function apply(CartInterface $quote, array $codes, bool $remove = false): void
    {
        $code = reset($codes);
        if ($remove) {
            $code = '';
        }
        $this->cartRepository->save($quote->setCouponCode($code)->collectTotals());
        if (!$remove && !$quote->getCouponCode()) {
            throw new LocalizedException(__('The coupon code "%1" is not valid.', $code));
        }
    }
}
