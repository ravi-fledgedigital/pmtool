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

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Customer\Test\Fixture\Customer as CustomerFixture;
use Magento\Multicoupon\Test\Fixture\AddQuoteCoupons as AddCouponsFixture;
use Magento\Multicoupon\Test\Fixture\ApplyCoupon;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\CustomerCart as CustomerCartFixture;
use Magento\SalesRule\Model\Rule as SalesRule;
use Magento\SalesRule\Test\Fixture\Rule as SalesRuleFixture;
use Magento\SalesRule\Test\Fixture\RuleCoupon as RuleCouponFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for QuoteRepository/Get plugin
 */
class GetTest extends TestCase
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    private const COUPON_CODES = ['COUPON-A', 'COUPON-B'];

    private const SINGLE_COUPON_CODE = 'COUPON-C';

    protected function setUp(): void
    {
        parent::setUp();
        $this->quoteRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
    }

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
    public function testAfterGetMultipleCouponsConfigEnabled()
    {
        $cart = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('cart');

        /** @var CartInterface $quote */
        $quote = $this->quoteRepository->get($cart->getId());
        $couponCodes = $quote->getExtensionAttributes()->getCouponCodes();

        $this->assertEquals(self::COUPON_CODES, $couponCodes);
    }

    #[
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]
        ),
        DataFixture(
            AddCouponsFixture::class,
            ['cart_id' => '$cart.id$', 'coupon_codes' => self::COUPON_CODES]
        )
    ]
    public function testAfterGetMultipleCouponsConfigNotEnabled()
    {
        $cart = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('cart');

        /** @var CartInterface $quote */
        $quote = $this->quoteRepository->get($cart->getId());
        $couponCodes = $quote->getExtensionAttributes()->getCouponCodes();

        $this->assertEmpty($couponCodes);
    }

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
    public function testAfterGetEmptyCoupon()
    {
        $cart = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('cart');

        /** @var CartInterface $quote */
        $quote = $this->quoteRepository->get($cart->getId());
        $couponCodes = $quote->getExtensionAttributes()->getCouponCodes();

        $this->assertEmpty($couponCodes);
    }

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
            SalesRuleFixture::class,
            [
                'store_labels' => [1 => 'Another Test Coupon label'],
                'coupon_type' => SalesRule::COUPON_TYPE_SPECIFIC,
                'simple_action' => SalesRule::BY_PERCENT_ACTION,
                'uses_per_customer' => 1,
                'stop_rules_processing' => false,
                'discount_amount' => 10
            ],
            as: 'rule1'
        ),
        DataFixture(
            RuleCouponFixture::class,
            [
                'rule_id' => '$rule1.id$',
                'code' => self::SINGLE_COUPON_CODE
            ]
        ),
    ]
    public function testAfterGetOnlyOneCouponAdded()
    {
        $cart = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('cart');
        Bootstrap::getObjectManager()->get(ApplyCoupon::class)->apply(
            [
                'cart_id' => $cart->getId(),
                'coupon_codes' => [
                    self::SINGLE_COUPON_CODE
                ]
            ]
        );

        $quote = $this->quoteRepository->get($cart->getId());
        $this->assertEquals(self::SINGLE_COUPON_CODE, $quote->getCouponCode());
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2', 'store'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(
            AddCouponsFixture::class,
            ['cart_id' => '$cart.id$', 'coupon_codes' => self::COUPON_CODES]
        )
    ]
    public function testAfterGetMultipleCouponAdded()
    {
        $cart = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('cart');

        /** @var CartInterface $quote */
        $quote = $this->quoteRepository->get($cart->getId());

        $this->assertEmpty($quote->getCouponCode());
    }
}
