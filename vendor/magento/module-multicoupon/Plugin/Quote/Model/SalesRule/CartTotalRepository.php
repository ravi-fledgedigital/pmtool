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

namespace Magento\Multicoupon\Plugin\Quote\Model\SalesRule;

use Magento\Quote\Api\Data\TotalsExtensionFactory;
use Magento\Quote\Model\Cart\CartTotalRepository as TotalRepository;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\SalesRule\Model\CouponFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;

class CartTotalRepository
{
    /**
     * @var TotalsExtensionFactory $totalsExtensionFactory
     */
    private $totalsExtensionFactory;

    /**
     * @var CouponFactory
     */
    private $couponFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @param TotalsExtensionFactory $totalsExtensionFactory
     * @param CouponFactory $couponFactory
     * @param StoreManagerInterface $storeManager
     * @param RuleRepositoryInterface $ruleRepository
     */
    public function __construct(
        TotalsExtensionFactory $totalsExtensionFactory,
        CouponFactory $couponFactory,
        StoreManagerInterface $storeManager,
        RuleRepositoryInterface $ruleRepository
    ) {
        $this->totalsExtensionFactory = $totalsExtensionFactory;
        $this->couponFactory = $couponFactory;
        $this->storeManager = $storeManager;
        $this->ruleRepository = $ruleRepository;
    }

    /**
     * Add coupons labels
     *
     * @param TotalRepository $subject
     * @param TotalsInterface $result
     * @return TotalsInterface
     */
    public function afterGet(TotalRepository $subject, TotalsInterface $result): TotalsInterface
    {
        if ($result->getExtensionAttributes() === null) {
            $extensionAttributes = $this->totalsExtensionFactory->create();
            $result->setExtensionAttributes($extensionAttributes);
        }

        $extensionAttributes = $result->getExtensionAttributes();
        $couponCodes = $extensionAttributes->getCouponCodes();

        if (empty($couponCodes)) {
            return $result;
        }

        $couponsLabels = null;
        foreach ($couponCodes as $couponCode) {
            $coupon = $this->couponFactory->create();
            $coupon->loadByCode($couponCode);
            $ruleId = $coupon->getRuleId();

            if (empty($ruleId)) {
                continue;
            }
            $storeId = $this->storeManager->getStore()->getId();
            $rule = $this->ruleRepository->getById($ruleId);

            $storeLabel = $storeLabelFallback = null;
            /* @var $label \Magento\SalesRule\Model\Data\RuleLabel */
            foreach ($rule->getStoreLabels() as $label) {
                if ($label->getStoreId() === 0) {
                    $storeLabelFallback = $label->getStoreLabel();
                }

                if ($label->getStoreId() == $storeId) {
                    $storeLabel = $label->getStoreLabel();
                    break;
                }
            }

            $couponsLabels[$couponCode] = ($storeLabel) ? $storeLabel : $storeLabelFallback;
        }
        $extensionAttributes->setCouponsLabels($couponsLabels);
        $result->setExtensionAttributes($extensionAttributes);

        return $result;
    }
}
