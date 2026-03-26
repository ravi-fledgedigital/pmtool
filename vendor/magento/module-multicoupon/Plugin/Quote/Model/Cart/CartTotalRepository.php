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

namespace Magento\Multicoupon\Plugin\Quote\Model\Cart;

use Magento\Multicoupon\Api\Quote\GetCouponsInterface;
use Magento\Quote\Api\Data\TotalsExtensionFactory;
use Magento\Quote\Model\Cart\CartTotalRepository as TotalRepository;
use Magento\Quote\Api\Data\TotalsInterface;

class CartTotalRepository
{
    /**
     * @var GetCouponsInterface $getQuoteCoupons
     */
    private $getQuoteCoupons;

    /**
     * @var TotalsExtensionFactory $totalsExtensionFactory
     */
    private $totalsExtensionFactory;

    /**
     * @param GetCouponsInterface $getQuoteCoupons
     * @param TotalsExtensionFactory $totalsExtensionFactory
     */
    public function __construct(
        GetCouponsInterface $getQuoteCoupons,
        TotalsExtensionFactory $totalsExtensionFactory
    ) {
        $this->getQuoteCoupons = $getQuoteCoupons;
        $this->totalsExtensionFactory = $totalsExtensionFactory;
    }

    /**
     * Add coupons code to quote totals extension attributes.
     *
     * @param TotalRepository $subject
     * @param TotalsInterface $totals
     * @param int $cartId
     * @return TotalsInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(TotalRepository $subject, TotalsInterface $totals, int $cartId): TotalsInterface
    {
        $extensionAttributes = $totals->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->totalsExtensionFactory->create();
            $totals->setExtensionAttributes($extensionAttributes);
        }

        if ($extensionAttributes->getCouponCodes() === null) {
            $couponCodes = $this->getQuoteCoupons->execute((string)$cartId);
            $extensionAttributes->setCouponCodes($couponCodes);
        }

        return $totals;
    }
}
