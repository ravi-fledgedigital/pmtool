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

namespace Magento\Multicoupon\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Multicoupon\Api\Quote\AddCouponsInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\TestFramework\Fixture\DataFixtureInterface;

class ApplyCoupon implements DataFixtureInterface
{
    /**
     * @var CartRepositoryInterface
     */
    public CartRepositoryInterface $quoteRepository;

    /**
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * {@inheritdoc}
     * @param array $data Parameters
     * <pre>
     *    $data = [
     *      'cart_id'    => (string) Cart ID. Required.
     *      'coupon_codes' => (array) Coupon Codes. Required.
     *    ]
     * </pre>
     */
    public function apply(array $data = []): ?DataObject
    {
        if (empty($data['cart_id']) || empty($data['coupon_codes'])) {
            throw new \InvalidArgumentException('cart_id or coupon_codes is missing!');
        }
        $quote = $this->quoteRepository->getActive($data['cart_id']);
        $quote->setCouponCode(reset($data['coupon_codes']));
        $quote->getExtensionAttributes()->setCouponCodes($data['coupon_codes']);
        $this->quoteRepository->save($quote->collectTotals());
        return $quote;
    }
}
