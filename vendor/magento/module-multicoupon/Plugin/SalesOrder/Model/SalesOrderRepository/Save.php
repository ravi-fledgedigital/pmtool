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

namespace Magento\Multicoupon\Plugin\SalesOrder\Model\SalesOrderRepository;

use Magento\Multicoupon\Api\SalesOrder\ReplaceCouponsInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Multicoupon\Model\Config\Config as MulticouponConfig;
use Magento\Sales\Model\Order;

class Save
{
    /**
     * @param ReplaceCouponsInterface $setCoupons
     * @param MulticouponConfig $config
     */
    public function __construct(
        private ReplaceCouponsInterface $setCoupons,
        private MulticouponConfig $config
    ) {
    }

    /**
     * Sets the coupon codes associated to a specific order.
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $result
     * @param OrderInterface $entity
     * @return OrderInterface
     */
    public function afterSave(
        OrderRepositoryInterface $subject,
        OrderInterface $result,
        OrderInterface $entity
    ): OrderInterface {
        if (!($this->config->getMaximumNumberOfCoupons() > 1)) {
            return $result;
        }
        if ($entity->getExtensionAttributes()->getCouponDiscounts() === null) {
            return $result;
        }
        $couponDiscounts = $entity->getExtensionAttributes()->getCouponDiscounts();
        if ($couponDiscounts && $entity->getEntityId()) {
            $this->setCoupons->execute((string)$entity->getEntityId(), $couponDiscounts);
        }

        return $result;
    }
}
