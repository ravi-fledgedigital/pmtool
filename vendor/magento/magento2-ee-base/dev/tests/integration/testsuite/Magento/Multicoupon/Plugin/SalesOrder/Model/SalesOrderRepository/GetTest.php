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

namespace Magento\Multicoupon\Plugin\SalesOrder\Model\SalesOrderRepository;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Customer\Test\Fixture\Customer;
use Magento\Multicoupon\Test\Fixture\AddSalesOrderCoupons as AddCouponsFixture;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for SalesOrderRepository/Get plugin
 */
class GetTest extends TestCase
{
    /** @var OrderRepositoryInterface  */
    private $orderRepository;

    private const COUPON_CODES = ['COUPON-A'=>1.5, 'COUPON-B'=>2.0];

    private const SINGLE_COUPON_CODE = ['COUPON-C'=>1.2];

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderRepository = Bootstrap::getObjectManager()->get(OrderRepositoryInterface::class);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2', 'store'),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'billingAddress'),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'shippingAddress'),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], as: 'order'),
        DataFixture(
            AddCouponsFixture::class,
            ['order_id' => '$order.id$', 'coupon_codes' => self::COUPON_CODES]
        )
    ]
    public function testAfterGetMultipleCouponsConfigEnabled()
    {
        $order = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('order');

        /** @var OrderInterface $placedOrder */
        $placedOrder = $this->orderRepository->get($order->getEntityId());
        $placedOrder->getExtensionAttributes()->setCouponCodes(null);
        $placedOrder->getExtensionAttributes()->setCouponDiscounts(null);
        $placedOrder = $this->orderRepository->get($order->getEntityId());
        $couponCodes = $placedOrder->getExtensionAttributes()->getCouponCodes();
        $couponDiscounts = $placedOrder->getExtensionAttributes()->getCouponDiscounts();
        $this->assertEquals(array_keys(self::COUPON_CODES), $couponCodes);
        $this->assertEquals(self::COUPON_CODES, $couponDiscounts);

        //TODO resolve integrity constraint violation on saving cancelled order
        $placedOrder->getExtensionAttributes()->setCouponDiscounts([]);
    }

    #[
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'billingAddress'),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'shippingAddress'),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], as: 'order'),
        DataFixture(
            AddCouponsFixture::class,
            ['order_id' => '$order.id$', 'coupon_codes' => self::COUPON_CODES]
        )
    ]
    public function testAfterGetMultipleCouponsConfigNotEnabled()
    {
        $order = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('order');

        /** @var OrderInterface $placedOrder */
        $placedOrder = $this->orderRepository->get($order->getEntityId());
        $couponCodes = $placedOrder->getExtensionAttributes()->getCouponCodes();
        $this->assertEmpty($couponCodes);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2', 'store'),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'billingAddress'),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'shippingAddress'),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], as: 'order')
    ]
    public function testAfterGetEmptyCoupons()
    {
        $order = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('order');

        /** @var OrderInterface $placedOrder */
        $placedOrder = $this->orderRepository->get($order->getEntityId());
        $couponCodes = $placedOrder->getExtensionAttributes()->getCouponCodes();
        $this->assertEmpty($couponCodes);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2', 'store'),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'billingAddress'),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'shippingAddress'),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], as: 'order'),
        DataFixture(
            AddCouponsFixture::class,
            ['order_id' => '$order.id$', 'coupon_codes' => self::SINGLE_COUPON_CODE]
        )
    ]
    public function testAfterGetOnlyOneCouponAdded()
    {
        $order = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('order');

        /** @var OrderInterface $placedOrder */
        $placedOrder = $this->orderRepository->get($order->getEntityId());
        $placedOrder->getExtensionAttributes()->setCouponDiscounts(null);
        $placedOrder = $this->orderRepository->get($order->getEntityId());

        $this->assertEquals(['COUPON-C'], $placedOrder->getExtensionAttributes()->getCouponCodes());
        $couponDiscounts = $placedOrder->getExtensionAttributes()->getCouponDiscounts();
        $this->assertEquals(self::SINGLE_COUPON_CODE, $couponDiscounts);

        //unsetting the coupon code to avoid Integrity constraint violation in PlaceOrderFixture revert method
        $placedOrder->getExtensionAttributes()->setCouponDiscounts([]);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2', 'store'),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'billingAddress'),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'shippingAddress'),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], as: 'order'),
        DataFixture(
            AddCouponsFixture::class,
            ['order_id' => '$order.id$', 'coupon_codes' => self::COUPON_CODES]
        )
    ]
    public function testAfterGetMultipleCouponAdded()
    {
        $order = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('order');

        /** @var OrderInterface $placedOrder */
        $placedOrder = $this->orderRepository->get($order->getEntityId());

        $this->assertEmpty($placedOrder->getCouponCode());

        //unsetting the coupon code to avoid Integrity constraint violation in PlaceOrderFixture revert method
        $placedOrder->getExtensionAttributes()->setCouponCodes([]);
        $placedOrder->getExtensionAttributes()->setCouponDiscounts([]);
    }
}
