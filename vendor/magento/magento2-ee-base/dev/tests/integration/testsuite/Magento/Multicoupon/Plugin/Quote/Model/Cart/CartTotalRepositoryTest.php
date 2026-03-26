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

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Customer\Test\Fixture\Customer as CustomerFixture;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Multicoupon\Test\Fixture\AddQuoteCoupons as AddCouponsFixture;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\CustomerCart as CustomerCartFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Check that coupons are present in quote totals
 */
class CartTotalRepositoryTest extends TestCase
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var CartTotalRepositoryInterface
     */
    private $cartTotalRepository;

    private const COUPON_CODES = ['abcd123', 'qwerty890'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->quoteRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
        $this->cartTotalRepository = Bootstrap::getObjectManager()->get(CartTotalRepositoryInterface::class);
    }

    /**
     * @return void
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2', 'store'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]
        ),
        DataFixture(
            AddCouponsFixture::class,
            ['cart_id' => '$cart.id$', 'coupon_codes' => self::COUPON_CODES]
        )
    ]
    public function testGetQuoteTotalsWithCoupons()
    {
        /** @var CartInterface $cart */
        $cart = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('cart');
        $quote = $this->quoteRepository->get($cart->getId());
        $this->assertEquals(self::COUPON_CODES, $quote->getExtensionAttributes()->getCouponCodes());
        $totals = $this->cartTotalRepository->get($cart->getId());
        $this->assertEquals(
            $quote->getExtensionAttributes()->getCouponCodes(),
            $totals->getExtensionAttributes()->getCouponCodes()
        );
    }

    /**
     * @return void
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2', 'store'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]
        )
    ]
    public function testGetQuoteTotalsWithoutCoupons()
    {
        /** @var CartInterface $cart */
        $cart = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('cart');
        $quote = $this->quoteRepository->get($cart->getId());
        $this->assertEquals([], $quote->getExtensionAttributes()->getCouponCodes());
        $totals = $this->cartTotalRepository->get($cart->getId());
        $this->assertEquals(
            $quote->getExtensionAttributes()->getCouponCodes(),
            $totals->getExtensionAttributes()->getCouponCodes()
        );
    }
}
