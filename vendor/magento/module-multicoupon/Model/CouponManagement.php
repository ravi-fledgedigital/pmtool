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

namespace Magento\Multicoupon\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Multicoupon\Api\CouponManagementInterface;
use Magento\Quote\Api\CouponManagementInterface as QuoteCouponManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Multicoupon\Model\Config\Config as MulticouponConfig;
use Magento\Quote\Api\Data\CartInterface;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\SalesRule\Model\ValidateCouponCode;

/**
 * Coupon management object.
 */
class CouponManagement implements CouponManagementInterface
{
    /**
     * @param MulticouponConfig $config
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteCouponManagementInterface $quoteCouponManagement
     * @param ValidateCouponCode $validateCouponCode
     * @param CouponRepositoryInterface $couponRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     */
    public function __construct(
        private readonly MulticouponConfig $config,
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly QuoteCouponManagementInterface $quoteCouponManagement,
        private readonly ValidateCouponCode $validateCouponCode,
        private readonly CouponRepositoryInterface $couponRepository,
        private readonly SearchCriteriaBuilder $criteriaBuilder
    ) {
    }

    /**
     * @inheritDoc
     */
    public function get(int $cartId): array
    {
        $quote = $this->quoteRepository->get($cartId);
        $coupons = $quote->getExtensionAttributes()->getCouponCodes() ?? [];
        if ($quote->getCouponCode() && !in_array($quote->getCouponCode(), $coupons)) {
            array_unshift($coupons, $quote->getCouponCode());
        }
        return $coupons;
    }

    /**
     * @inheritDoc
     */
    public function append(int $cartId, array $couponCodes): void
    {
        if (empty($couponCodes)) {
            return;
        }
        $quote = $this->quoteRepository->get($cartId);
        $appliedCouponCodes = $quote->getExtensionAttributes()->getCouponCodes() ?? [];
        if ($quote->getCouponCode() && !in_array($quote->getCouponCode(), $appliedCouponCodes)) {
            array_unshift($appliedCouponCodes, $quote->getCouponCode());
        }
        if (empty(array_diff($couponCodes, $appliedCouponCodes))) {
            return;
        }
        $allCouponCodes = array_unique(array_merge($appliedCouponCodes, $couponCodes));
        $this->apply($quote, $allCouponCodes);
        $quote->collectTotals();
        $notAppliedCoupons = $this->getNotAppliedCoupons($quote, $allCouponCodes);
        $this->quoteRepository->save($quote);
        if (!empty($notAppliedCoupons)) {
            throw new InputException(
                __('The following coupon codes could not be applied: "' . implode('","', $notAppliedCoupons) . '".')
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function replace(int $cartId, array $couponCodes): void
    {
        $quote = $this->quoteRepository->get($cartId);
        $this->apply($quote, $couponCodes);
        $quote->collectTotals();
        $notAppliedCoupons = $this->getNotAppliedCoupons($quote, $couponCodes);
        $this->quoteRepository->save($quote);
        if (!empty($notAppliedCoupons)) {
            throw new InputException(
                __('The following coupon codes could not be applied: "' . implode('","', $notAppliedCoupons) . '".')
            );
        }
    }

    /**
     * Apply coupons to quote
     *
     * @param CartInterface $quote
     * @param string[] $couponCodes
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function apply(CartInterface $quote, array $couponCodes)
    {
        if (count($couponCodes) > $this->config->getMaximumNumberOfCoupons()) {
            throw new InputException(
                __('Maximum allowed number of applied coupons was exceeded.')
            );
        }
        $this->validateCouponCodes($quote, $couponCodes);
        $quote->setCouponCode(reset($couponCodes));
        $quote->getExtensionAttributes()->setCouponCodes($couponCodes);
    }

    /**
     * Validate coupon code(s) before the calculation
     *
     * @param CartInterface $quote
     * @param string[] $couponCodes
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function validateCouponCodes(CartInterface $quote, array $couponCodes): void
    {
        if (!$quote->getItemsCount()) {
            throw new InputException(__('Cart does not contain products.'));
        }

        $customerId = null;
        if (!$quote->getCustomerIsGuest() && $quote->getCustomer()) {
            $customerId = (int)$quote->getCustomer()->getId();
        }

        $invalidCouponCodes = array_diff(
            $couponCodes,
            array_values($this->validateCouponCode->execute($couponCodes, $customerId))
        );

        if (!empty($invalidCouponCodes)) {
            throw new InputException(
                __('The following coupon codes could not be applied: "' . implode('","', $invalidCouponCodes) . '".')
            );
        }
    }

    /**
     * Check if all the coupons have been successfully applied to the quote
     *
     * @param CartInterface $quote
     * @param string[] $couponCodes
     * @return string[]
     * @throws InputException|LocalizedException
     */
    private function getNotAppliedCoupons(CartInterface $quote, array $couponCodes): array
    {
        $couponModels = $this->couponRepository->getList(
            $this->criteriaBuilder->addFilter(
                'code',
                $couponCodes,
                'in'
            )->create()
        )->getItems();

        $appliedCoupons = [];

        $appliedRuleIds = $quote->getAppliedRuleIds() ? explode(',', $quote->getAppliedRuleIds()) : [];

        foreach ($couponModels as $couponModel) {
            if (in_array($couponModel->getRuleId(), $appliedRuleIds)) {
                $appliedCoupons[] = $couponModel->getCode();
            }
        }

        $quote->getExtensionAttributes()->setCouponCodes($appliedCoupons);
        if (!empty($appliedCoupons)) {
            $quote->setCouponCode(reset($appliedCoupons));
        } else {
            $quote->setCouponCode('');
        }

        return array_diff($couponCodes, $appliedCoupons);
    }

    /**
     * @inheritDoc
     */
    public function remove(int $cartId, array $couponCodes = []): void
    {
        $quote = $this->quoteRepository->get($cartId);
        // If multi-coupon module is disabled
        if ($this->config->getMaximumNumberOfCoupons() == 1) {
            $quote->getExtensionAttributes()->setCouponCodes([]);
            $this->quoteCouponManagement->remove($cartId);
            return;
        }

        $existingCouponCodes = $quote->getExtensionAttributes()->getCouponCodes() ?? [];
        $couponCodes = array_filter($couponCodes);
        if (empty($couponCodes)) {
            $couponCodes = $existingCouponCodes;
        }
        $remainingCouponCodes = array_filter(array_diff($existingCouponCodes, $couponCodes));
        if (!empty($remainingCouponCodes)) {
            $quote->setCouponCode(reset($remainingCouponCodes));
            $quote->getExtensionAttributes()->setCouponCodes($remainingCouponCodes);
        } else {
            $quote->setCouponCode('');
            $quote->getExtensionAttributes()->setCouponCodes([]);
        }

        $this->quoteRepository->save($quote->collectTotals());
    }
}
