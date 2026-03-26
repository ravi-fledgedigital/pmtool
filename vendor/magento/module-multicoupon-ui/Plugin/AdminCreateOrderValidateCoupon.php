<?php
/************************************************************************
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\MulticouponUi\Plugin;

use Magento\Framework\Escaper;
use Magento\Framework\Message\ManagerInterface;
use Magento\Multicoupon\Model\Config\Config;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order\Create\ValidateCoupon;

class AdminCreateOrderValidateCoupon
{
    /**
     * @param Config $config
     * @param ManagerInterface $messageManager
     * @param Escaper $escaper
     */
    public function __construct(
        private readonly Config $config,
        private readonly ManagerInterface $messageManager,
        private readonly Escaper $escaper
    ) {
    }

    /**
     * Validate coupon applied to quote
     *
     * @param ValidateCoupon $subject
     * @param \Closure $proceed
     * @param CartInterface $quote
     * @param array $data
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function aroundExecute(ValidateCoupon $subject, \Closure $proceed, CartInterface $quote, $data): void
    {
        $code = '';
        if (isset($data['coupon']['code'])) {
            $code = trim($data['coupon']['code']);
        }

        if (empty($code)) {
            if (isset($data['coupon']['code']) && $code == '') {
                $this->messageManager->addSuccessMessage(__('The coupon code has been removed.'));
            }
            return;
        }

        if (!$this->isApplyDiscount($quote)) {
            $this->messageManager->addErrorMessage(
                __(
                    '"%1" coupon code was not applied. Do not apply discount is selected for item(s)',
                    $this->escaper->escapeHtml($code)
                )
            );
            return;
        }

        if (in_array($code, $quote->getExtensionAttributes()->getCouponCodes())) {
            $this->messageManager->addSuccessMessage(__('The coupon code has been accepted.'));
            return;
        }

        if (count($quote->getExtensionAttributes()->getCouponCodes())
            >= $this->config->getMaximumNumberOfCoupons()
        ) {
            $this->messageManager->addErrorMessage(
                __('Maximum allowed number of applied coupons was exceeded.')
            );
        } else {
            $this->messageManager->addErrorMessage(
                __(
                    'The "%1" coupon code isn\'t valid. Verify the code and try again.',
                    $this->escaper->escapeHtml($code)
                )
            );
        }
    }

    /**
     * Check if discount is applied to quote items
     *
     * @param CartInterface $quote
     * @return bool
     */
    private function isApplyDiscount(CartInterface $quote): bool
    {
        foreach ($quote->getAllItems() as $item) {
            if (!$item->getNoDiscount()) {
                return true;
            }
        }
        return false;
    }
}
