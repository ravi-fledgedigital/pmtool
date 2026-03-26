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

use Magento\Multicoupon\Api\Quote\ReplaceCouponsInterface;
use Magento\Multicoupon\Model\Config\Config;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\CartRepositoryInterface;

class Save
{
    /**
     * @param ReplaceCouponsInterface $setCoupons
     * @param Config $config
     */
    public function __construct(
        private readonly ReplaceCouponsInterface $setCoupons,
        private readonly Config $config
    ) {
    }

    /**
     * Sets the coupon codes associated to a specific quote.
     *
     * @param CartRepositoryInterface $subject
     * @param mixed $result
     * @param CartInterface $entity
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        CartRepositoryInterface $subject,
        mixed $result,
        CartInterface $entity
    ) {
        if ($this->config->getMaximumNumberOfCoupons() > 1) {
            $this->setCoupons->execute(
                (string) $entity->getId(),
                $entity->getExtensionAttributes()->getCouponCodes() ?? []
            );
        }
        return $result;
    }
}
