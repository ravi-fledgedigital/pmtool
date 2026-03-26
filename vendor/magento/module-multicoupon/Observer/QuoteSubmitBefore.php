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

namespace Magento\Multicoupon\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\SalesRule\Model\SelectRuleCoupon;

class QuoteSubmitBefore implements ObserverInterface
{
    /**
     * @param SelectRuleCoupon $selectRuleCoupon
     */
    public function __construct(
        private readonly SelectRuleCoupon $selectRuleCoupon
    ) {
    }

    /**
     * Set coupon specific discount in order
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $this->setCouponDiscounts(
            $observer->getEvent()->getData('order'),
            $this->getCouponDiscounts($observer->getEvent()->getData('quote'))
        );
    }

    /**
     * Set coupon discounts to order extension attributes
     *
     * @param OrderInterface $order
     * @param array $discounts
     * @return void
     */
    private function setCouponDiscounts(OrderInterface $order, array $discounts): void
    {
        $order->getExtensionAttributes()->setCouponDiscounts($discounts);
    }

    /**
     * Get coupon discounts from quote totals
     *
     * @param Quote $quote
     * @return array
     */
    private function getCouponDiscounts(Quote $quote): array
    {
        $address = $quote->getShippingAddress();
        $couponCodes = $quote->getExtensionAttributes()->getCouponCodes();
        if (!$couponCodes) {
            return [];
        }
        $totalDiscounts = $address->getExtensionAttributes()->getDiscounts();
        if (!is_array($totalDiscounts)) {
            return [];
        }
        $discounts = [];
        $ruleCoupons = array_flip($this->selectRuleCoupon->getCouponsToRules($couponCodes));
        foreach ($totalDiscounts as $value) {
            if ($value->getRuleId()) {
                if (isset($ruleCoupons[$value->getRuleId()])) {
                    $discounts[$ruleCoupons[$value->getRuleId()]] = $value->getDiscountData()->getAmount();
                }
            }
        }
        return $discounts;
    }
}
