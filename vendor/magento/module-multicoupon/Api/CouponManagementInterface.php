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

namespace Magento\Multicoupon\Api;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Coupon management service interface.
 */
interface CouponManagementInterface
{
    /**
     * Returns information for all coupons in a specified cart.
     *
     * @param int $cartId The cart ID.
     * @return string[] The coupon code data.
     * @throws NoSuchEntityException The specified cart does not exist.
     */
    public function get(int $cartId): array;

    /**
     * Append the coupon code(s) to cart
     *
     * @param int $cartId
     * @param string[] $couponCodes
     * @return void
     * @throws InputException
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function append(int $cartId, array $couponCodes): void;

    /**
     * Replace the coupon code(s) in cart with the new code(s)
     *
     * @param int $cartId
     * @param string[] $couponCodes
     * @return void
     * @throws InputException
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function replace(int $cartId, array $couponCodes): void;

    /**
     * Deletes coupon(s) from a specified cart.
     *
     * @param int $cartId The cart ID.
     * @param string[] $couponCodes coupon codes
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException The specified cart does not exist.
     * @throws CouldNotDeleteException The specified coupon could not be deleted.
     */
    public function remove(int $cartId, array $couponCodes = []): void;
}
