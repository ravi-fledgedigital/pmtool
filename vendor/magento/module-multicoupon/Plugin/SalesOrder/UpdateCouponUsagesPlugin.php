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

namespace Magento\Multicoupon\Plugin\SalesOrder;

use Magento\Framework\App\ObjectManager;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Model\Coupon\UpdateCouponUsages;
use Magento\SalesRule\Model\Coupon\Usage\Processor as CouponUsageProcessor;
use Magento\SalesRule\Model\Coupon\Usage\UpdateInfoFactory;
use Magento\SalesRule\Model\Service\CouponUsagePublisher;

class UpdateCouponUsagesPlugin
{
    /**
     * @var CouponUsagePublisher
     */
    private $couponUsagePublisher;

    /**
     * @param CouponUsageProcessor $couponUsageProcessor
     * @param UpdateInfoFactory $updateInfoFactory
     * @param CouponUsagePublisher|null $couponUsagePublisher
     */
    public function __construct(
        private readonly CouponUsageProcessor $couponUsageProcessor,
        private readonly UpdateInfoFactory $updateInfoFactory,
        CouponUsagePublisher $couponUsagePublisher = null
    ) {
        $this->couponUsagePublisher = $couponUsagePublisher
            ?? ObjectManager::getInstance()->get(CouponUsagePublisher::class);
    }

    /**
     * Executes the current command
     *
     * @param UpdateCouponUsages $subject
     * @param \Closure $proceed
     * @param OrderInterface $order
     * @param bool $increment
     * @return OrderInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function aroundExecute(
        UpdateCouponUsages $subject,
        \Closure $proceed,
        OrderInterface $order,
        bool $increment
    ): OrderInterface {
        if (!$order || !$order->getAppliedRuleIds()) {
            return $order;
        }
        $isCouponAlreadyApplied = null;
        if ($order->getOrigData('coupon_code') !== null
            && $order->getStatus() !== Order::STATE_CANCELED
        ) {
            $isCouponAlreadyApplied = true;
        }

        $updateInfo = $this->updateInfoFactory->create(
            [
                'data' => [
                    'applied_rule_ids' => $this->getAppliedRuleIds($order),
                    'coupon_code' => (string)$order->getCouponCode(),
                    'customer_id' => (int)$order->getCustomerId(),
                    'is_increment' => $increment,
                    'is_coupon_already_applied' => $isCouponAlreadyApplied,
                    'coupon_codes' => $order->getExtensionAttributes()->getCouponCodes() ?? [],
                ]
            ]
        );

        $this->couponUsageProcessor->updateCustomerRulesUsages($updateInfo);
        $this->couponUsageProcessor->updateCouponUsages($updateInfo);
        $this->couponUsagePublisher->publish($updateInfo);

        return $order;
    }

    /**
     * Return applied rule ids from Order
     *
     * @param OrderInterface $order
     * @return array
     */
    private function getAppliedRuleIds(OrderInterface $order): array
    {
        $appliedRuleIds = explode(',', $order->getAppliedRuleIds());
        return array_filter(array_map('intval', array_unique($appliedRuleIds)));
    }
}
