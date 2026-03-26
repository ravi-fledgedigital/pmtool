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

namespace Magento\Multicoupon\Plugin\Quote\Model\QuoteManagement;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Customer\Test\Fixture\Customer as CustomerFixture;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Multicoupon\Test\Fixture\AddQuoteCoupons as AddCouponsFixture;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\CustomerCart as CustomerCartFixture;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\SalesRule\Model\Rule as SalesRule;
use Magento\SalesRule\Test\Fixture\Rule as SalesRuleFixture;
use Magento\SalesRule\Test\Fixture\RuleCoupon as RuleCouponFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Check that coupons from quote are applied when an order is placed
 */
class PlaceOrderTest extends TestCase
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    private const COUPON_CODES = ['abcd123', 'qwerty890'];

    private const COUPON_CODE_A = 'abcd123';

    private const COUPON_CODE_B = 'qwerty890';

    private const SINGLE_COUPON_CODE = 'xyz123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->quoteRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
        $this->orderRepository = Bootstrap::getObjectManager()->get(OrderRepositoryInterface::class);
        $this->cartManagement = Bootstrap::getObjectManager()->get(CartManagementInterface::class);
    }

    /**
     * @return void
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2', 'store'),
        DataFixture(
            SalesRuleFixture::class,
            [
                'store_labels' => [1 => 'Another Test Coupon label'],
                'coupon_type' => SalesRule::COUPON_TYPE_SPECIFIC,
                'simple_action' => \Magento\SalesRule\Model\Rule::BY_PERCENT_ACTION,
                'uses_per_customer' => 1,
                'stop_rules_processing' => false,
                'discount_amount' => 10
            ],
            as: 'rule1'
        ),
        DataFixture(
            SalesRuleFixture::class,
            [
                'store_labels' => [1 => 'Another Test Coupon label'],
                'coupon_type' => SalesRule::COUPON_TYPE_SPECIFIC,
                'simple_action' => \Magento\SalesRule\Model\Rule::BY_PERCENT_ACTION,
                'uses_per_customer' => 1,
                'discount_amount' => 10
            ],
            as: 'rule2'
        ),
        DataFixture(
            RuleCouponFixture::class,
            [
                'rule_id' => '$rule1.id$',
                'code' => self::COUPON_CODE_A
            ]
        ),
        DataFixture(
            RuleCouponFixture::class,
            [
                'rule_id' => '$rule2.id$',
                'code' => self::COUPON_CODE_B
            ]
        ),
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
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'billingAddress'),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'shippingAddress'),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
    ]
    public function testPlaceOrderWithCoupons()
    {
        /** @var CartInterface $cart */
        $cart = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('cart');
        $quote = $this->quoteRepository->get($cart->getId());
        $this->assertEquals(self::COUPON_CODES, array_values($quote->getExtensionAttributes()->getCouponCodes()));

        $orderId = (int) $this->cartManagement->placeOrder($cart->getId());
        /** @var OrderInterface $order */
        $order = $this->orderRepository->get($orderId);
        $this->assertEquals(self::COUPON_CODES, $order->getExtensionAttributes()->getCouponCodes());
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
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'billingAddress'),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'shippingAddress'),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
    ]
    public function testPlaceOrderWithoutCoupons()
    {
        /** @var CartInterface $cart */
        $cart = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('cart');
        $quote = $this->quoteRepository->get($cart->getId());
        $this->assertEquals([], $quote->getExtensionAttributes()->getCouponCodes());

        $orderId = (int) $this->cartManagement->placeOrder($cart->getId());
        /** @var OrderInterface $order */
        $order = $this->orderRepository->get($orderId);
        $this->assertEquals([], $order->getExtensionAttributes()->getCouponCodes());
    }

    /**
     * @return void
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2', 'store'),
        DataFixture(
            SalesRuleFixture::class,
            [
                'store_labels' => [1 => 'Another Test Coupon label'],
                'coupon_type' => SalesRule::COUPON_TYPE_SPECIFIC,
                'simple_action' => \Magento\SalesRule\Model\Rule::BY_PERCENT_ACTION,
                'uses_per_customer' => 1,
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
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]
        ),
        DataFixture(
            AddCouponsFixture::class,
            ['cart_id' => '$cart.id$', 'coupon_codes' => [self::SINGLE_COUPON_CODE]]
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'billingAddress'),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'shippingAddress'),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
    ]
    public function testPlaceOrderWithSingleCoupon()
    {
        /** @var CartInterface $cart */
        $cart = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('cart');
        $orderId = (int) $this->cartManagement->placeOrder($cart->getId());

        /** @var OrderInterface $order */
        $order = $this->orderRepository->get($orderId);
        $this->assertEquals(self::SINGLE_COUPON_CODE, $order->getCouponCode());
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
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'billingAddress'),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'shippingAddress'),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
    ]
    public function testPlaceOrderWithMultipleCoupons()
    {
        /** @var CartInterface $cart */
        $cart = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('cart');
        $orderId = (int) $this->cartManagement->placeOrder($cart->getId());

        /** @var OrderInterface $order */
        $order = $this->orderRepository->get($orderId);
        $this->assertEmpty($order->getCouponCode());
    }
}
