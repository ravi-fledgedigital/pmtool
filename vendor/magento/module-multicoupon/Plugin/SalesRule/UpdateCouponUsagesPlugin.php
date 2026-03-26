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

namespace Magento\Multicoupon\Plugin\SalesRule;

use Magento\Quote\Api\Data\CartInterface;
use Magento\SalesRule\Model\Coupon\Quote\UpdateCouponUsages;
use Magento\SalesRule\Model\Coupon\Usage\Processor as CouponUsageProcessor;
use Magento\SalesRule\Model\Coupon\Usage\UpdateInfoFactory;
use Magento\SalesRule\Model\Service\CouponUsagePublisher;

class UpdateCouponUsagesPlugin
{
    /**
     * @param CouponUsagePublisher $couponUsagePublisher
     * @param UpdateInfoFactory $updateInfoFactory
     * @param CouponUsageProcessor $processor
     */
    public function __construct(
        private readonly CouponUsagePublisher $couponUsagePublisher,
        private readonly UpdateInfoFactory $updateInfoFactory,
        private readonly CouponUsageProcessor $processor
    ) {
    }

    /**
     * Update multiple coupon usages
     *
     * @param UpdateCouponUsages $subject
     * @param \Closure $proceed
     * @param CartInterface $quote
     * @param bool $increment
     * @return void
     */
    public function aroundExecute(
        UpdateCouponUsages $subject,
        \Closure $proceed,
        CartInterface $quote,
        bool $increment
    ): void {
        if (!$quote->getAppliedRuleIds()) {
            return;
        }

        $appliedRuleIds = explode(',', $quote->getAppliedRuleIds());
        $appliedRuleIds = array_filter(array_map('intval', array_unique($appliedRuleIds)));

        $updateInfo = $this->updateInfoFactory->create(
            [
                'data' => [
                    'applied_rule_ids' => $appliedRuleIds,
                    'coupon_code' => (string)$quote->getCouponCode(),
                    'customer_id' => (int)$quote->getCustomerId(),
                    'is_increment' => $increment,
                    'coupon_codes' => $quote->getExtensionAttributes()->getCouponCodes() ?? [],
                ]
            ]
        );

        $this->processor->updateCouponUsages($updateInfo);
        $this->processor->updateCustomerRulesUsages($updateInfo);
        $this->couponUsagePublisher->publish($updateInfo);
    }
}
