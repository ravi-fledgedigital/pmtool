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
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Multicoupon\Model\Config\Config as MulticouponConfig;

class GetList
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
     * Lists the coupon codes that match the specified search criteria associated to an order.
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $searchResults
     * @return OrderSearchResultInterface
     */
    public function afterGetList(
        OrderRepositoryInterface $subject,
        OrderSearchResultInterface $searchResults
    ): OrderSearchResultInterface {
        if (!($this->config->getMaximumNumberOfCoupons() > 1)) {
            return $searchResults;
        }
        foreach ($searchResults->getItems() as $entity) {
            if ($entity->getExtensionAttributes()->getCouponCodes() !== null
                && $entity->getExtensionAttributes()->getCouponDiscounts() !== null) {
                continue;
            }
            $couponDiscounts = $this->getCoupons->execute((string)$entity->getEntityId());
            $extensionAttributes = $entity->getExtensionAttributes();
            $extensionAttributes->setCouponCodes(array_keys($couponDiscounts));
            $extensionAttributes->setCouponDiscounts($couponDiscounts);
            $entity->setExtensionAttributes($extensionAttributes);
        }
        /** @var OrderSearchResultInterface $newSearchResult */
        return $searchResults;
    }
}
