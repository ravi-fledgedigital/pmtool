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

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Multicoupon\Api\CouponManagementInterface;
use Magento\Multicoupon\Model\Config\Config;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Model\ValidateCouponCode;

class AdminCreateOrderApplyCoupons
{
    /**
     * @param Config $config
     * @param CouponManagementInterface $couponManagement
     * @param ManagerInterface $messageManager
     * @param ValidateCouponCode $validateCouponCode
     */
    public function __construct(
        private readonly Config $config,
        private readonly CouponManagementInterface $couponManagement,
        private readonly ManagerInterface $messageManager,
        private readonly ValidateCouponCode $validateCouponCode
    ) {
    }

    /**
     * Process multiple coupons after import post data
     *
     * @param Create $subject
     * @param Create $result
     * @param array $data
     * @return Create
     */
    public function afterImportPostData(Create $subject, Create $result, $data): Create
    {
        $quote = $result->getQuote();
        $couponCodes = $quote->getExtensionAttributes()->getCouponCodes() ?? [];
        if (isset($data['coupon']['append']) && $data['coupon']['append']) {
            $appendCouponCode = $data['coupon']['append'];

            if (!$this->isValidCouponCode($quote, $appendCouponCode)) {
                $quote->setCouponCode($quote->getOrigData('coupon_code'));
                $quote->getExtensionAttributes()->setCouponCodes([]);
                return $result;
            }

            if (!in_array($appendCouponCode, $couponCodes)) {
                array_unshift($couponCodes, $appendCouponCode);
            }
            if (count($couponCodes) <= $this->config->getMaximumNumberOfCoupons()) {
                $quote->getExtensionAttributes()->setCouponCodes($couponCodes);
                $quote->setCouponCode($appendCouponCode);
            } else {
                $quote->setCouponCode($quote->getOrigData('coupon_code'));
            }
        }
        if (isset($data['coupon']['remove']) && $data['coupon']['remove']) {
            $remainingCouponCodes = array_filter(array_diff($couponCodes, [$data['coupon']['remove']]));
            if (!empty($remainingCouponCodes)) {
                $quote->setCouponCode(reset($remainingCouponCodes));
                $quote->getExtensionAttributes()->setCouponCodes($remainingCouponCodes);
            } else {
                $quote->setCouponCode('');
                $quote->getExtensionAttributes()->setCouponCodes([]);
            }
        }
        return $result;
    }

    /**
     * Save multiple coupon codes and verify they have been applied
     *
     * @param Create $subject
     * @param \Closure $proceed
     * @return Create
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function aroundSaveQuote(Create $subject, \Closure $proceed): Create
    {
        $quote = $subject->getQuote();
        if (!$quote->getId()) {
            return $proceed();
        }
        $couponCodes = $quote->getExtensionAttributes()->getCouponCodes() ?? [];
        if (empty($couponCodes)) {
            return $proceed();
        }
        $primaryCoupon = $quote->getCouponCode();
        if ($primaryCoupon && !in_array($primaryCoupon, $couponCodes)) {
            array_unshift($couponCodes, $primaryCoupon);
        }
        try {
            $this->couponManagement->replace((int) $quote->getId(), $couponCodes);
        } catch (InputException $exception) {
            // Error messaging of primary (just added) coupon is handled by AdminCreateOrderValidateCoupon
            if ($primaryCoupon == $quote->getCouponCode()) {
                // Just added coupon is valid but already applied coupons are not valid
                $this->messageManager->addErrorMessage($exception->getMessage());
            }
        }

        return $subject;
    }

    /**
     * Process multiple coupons after init from order
     *
     * @param Create $subject
     * @param Create $result
     * @param Order $order
     * @return Create
     * @throws AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws NoSuchEntityException
     */
    public function afterInitFromOrder(Create $subject, Create $result, Order $order): Create
    {
        $orderCoupons = $order->getExtensionAttributes()->getCouponCodes() ?? [];
        if ($order->getCouponCode() && !in_array($order->getCouponCode(), $orderCoupons)) {
            array_unshift($orderCoupons, $order->getCouponCode());
        }
        if (!empty($orderCoupons)) {
            $this->couponManagement->replace((int)$result->getQuote()->getId(), $orderCoupons);
        }
        return $result;
    }

    /**
     * Validate the coupon code
     *
     * @param Quote $quote
     * @param string $appendCouponCode
     * @return bool
     */
    private function isValidCouponCode(Quote $quote, string $appendCouponCode): bool
    {
        $customerId = null;
        if (!$quote->getCustomerIsGuest() && $quote->getCustomer()) {
            $customerId = (int)$quote->getCustomer()->getId();
        }

        $invalidCouponCodes = array_diff(
            [$appendCouponCode],
            array_values($this->validateCouponCode->execute([$appendCouponCode], $customerId))
        );
        if (!empty($invalidCouponCodes)) {
            return false;
        }
        return true;
    }
}
