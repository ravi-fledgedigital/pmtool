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

use Magento\Multicoupon\Api\SalesOrder\GetCouponsInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Multicoupon\Model\Config\Config as MulticouponConfig;

class Get
{
    /**
     * @param GetCouponsInterface $getCoupons
     * @param MulticouponConfig $config
     */
    public function __construct(
        private GetCouponsInterface $getCoupons,
        private MulticouponConfig $config
    ) {
    }

    /**
     * Retrieves the coupon codes associated to a specific order.
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $entity
     * @return OrderInterface
     */
    public function afterGet(
        OrderRepositoryInterface $subject,
        OrderInterface $entity
    ): OrderInterface {
        if ($this->config->getMaximumNumberOfCoupons() <= 1) {
            return $entity;
        }
        if ($entity->getExtensionAttributes()->getCouponCodes() !== null
        && $entity->getExtensionAttributes()->getCouponDiscounts() !== null) {
            return $entity;
        }
        $couponDiscounts = $this->getCoupons->execute((string)$entity->getEntityId());
        $couponCodes = array_keys($couponDiscounts);
        $extensionAttributes = $entity->getExtensionAttributes();
        $extensionAttributes->setCouponCodes($couponCodes);
        $extensionAttributes->setCouponDiscounts($couponDiscounts);
        return $entity;
    }
}
