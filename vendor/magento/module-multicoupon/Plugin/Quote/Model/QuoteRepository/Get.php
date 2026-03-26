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

namespace Magento\Multicoupon\Plugin\Quote\Model\QuoteRepository;

use Magento\Multicoupon\Api\Quote\GetCouponsInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Multicoupon\Model\Config\Config;
use Magento\Quote\Api\Data\CartSearchResultsInterface;

class Get
{
    /**
     * @param GetCouponsInterface $getCoupons
     * @param Config $config
     */
    public function __construct(
        private readonly GetCouponsInterface $getCoupons,
        private readonly Config $config
    ) {
    }

    /**
     * Retrieves the coupon codes associated to a specific quote.
     *
     * @param CartRepositoryInterface $subject
     * @param CartInterface $entity
     * @return CartInterface
     */
    public function afterGet(
        CartRepositoryInterface $subject,
        CartInterface $entity
    ): CartInterface {
        if ($this->config->getMaximumNumberOfCoupons() > 1) {
            $this->setCoupons($entity);
        }
        return $entity;
    }

    /**
     * Retrieves the coupon codes associated to a specific quote.
     *
     * @param CartRepositoryInterface $subject
     * @param CartInterface $entity
     * @return CartInterface
     */
    public function afterGetForCustomer(
        CartRepositoryInterface $subject,
        CartInterface $entity
    ): CartInterface {
        if ($this->config->getMaximumNumberOfCoupons() > 1) {
            $this->setCoupons($entity);
        }
        return $entity;
    }

    /**
     * Lists the coupon codes that match the specified search criteria associated to a quote.
     *
     * @param CartRepositoryInterface $subject
     * @param CartSearchResultsInterface $searchResults
     * @return CartSearchResultsInterface
     */
    public function afterGetList(
        CartRepositoryInterface    $subject,
        CartSearchResultsInterface $searchResults
    ): CartSearchResultsInterface {
        if ($this->config->getMaximumNumberOfCoupons() > 1) {
            foreach ($searchResults->getItems() as $entity) {
                $this->setCoupons($entity);
            }
        }
        return $searchResults;
    }

    /**
     * Set coupon codes quote extension attribute
     *
     * @param CartInterface $quote
     * @return void
     */
    private function setCoupons(CartInterface $quote): void
    {
        if ($quote->getExtensionAttributes()->getCouponCodes() === null) {
            $couponCodes = $this->getCoupons->execute((string)$quote->getId());
            $quote->getExtensionAttributes()->setCouponCodes($couponCodes);
        }
    }
}
