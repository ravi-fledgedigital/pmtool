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
use Magento\Customer\Test\Fixture\Customer as CustomerFixture;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\CustomerCart as CustomerCartFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

#[
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
    DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order')
]
class SaveTest extends TestCase
{
    /**
     * @var \Magento\Multicoupon\Api\Quote\DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    private const COUPON_CODES = ['COUPON-A'=>1.5, 'COUPON-B'=>2.5];

    private const SINGLE_COUPON_CODE = ['COUPON-C'=>1.2];

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage();
        $this->orderRepository = Bootstrap::getObjectManager()->get(OrderRepositoryInterface::class);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2', 'store')
    ]
    public function testAfterSaveWithCouponsConfigEnabled()
    {
        $placeOrder = $this->fixtures->get('order');
        $this->assertEquals([], $placeOrder->getExtensionAttributes()->getCouponCodes());
        $placeOrder->getExtensionAttributes()->setCouponCodes(array_keys(self::COUPON_CODES));
        $placeOrder->getExtensionAttributes()->setCouponDiscounts(self::COUPON_CODES);
        $this->orderRepository->save($placeOrder);
        $placeOrder->getExtensionAttributes()->setCouponCodes(null);

        /** @var OrderInterface $order */
        $order = $this->orderRepository->get($placeOrder->getId());
        $couponCodes = $order->getExtensionAttributes()->getCouponCodes();
        $couponDiscounts = $order->getExtensionAttributes()->getCouponDiscounts();
        $this->assertEquals(array_keys(self::COUPON_CODES), $couponCodes);
        $this->assertEquals(self::COUPON_CODES, $couponDiscounts);

        //Unsetting the coupon code to avoid Integrity constraint violation in PlaceOrderFixture revert method
        $order->getExtensionAttributes()->setCouponCodes([]);
        $order->getExtensionAttributes()->setCouponDiscounts([]);
    }

    public function testAfterSaveWithCouponsConfigNotEnabled()
    {
        $placeOrder = $this->fixtures->get('order');
        $this->assertEquals(null, $placeOrder->getExtensionAttributes()->getCouponCodes());
        $placeOrder->getExtensionAttributes()->setCouponCodes(self::COUPON_CODES);
        $this->orderRepository->save($placeOrder);
        $placeOrder->getExtensionAttributes()->setCouponCodes([]);

        /** @var OrderInterface $order */
        $order = $this->orderRepository->get($placeOrder->getEntityId());
        $couponCodes = $order->getExtensionAttributes()->getCouponCodes();
        $this->assertEmpty($couponCodes);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2', 'store')
    ]
    public function testAfterSaveWithOutCoupons()
    {
        $placeOrder = $this->fixtures->get('order');
        $this->orderRepository->save($placeOrder);

        /** @var OrderInterface $order */
        $order = $this->orderRepository->get($placeOrder->getId());
        $couponCodes = $order->getExtensionAttributes()->getCouponCodes();
        $this->assertEmpty($couponCodes);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2', 'store')
    ]
    public function testBeforeSaveWithSingleCoupon()
    {
        $placeOrder = $this->fixtures->get('order');
        $placeOrder->getExtensionAttributes()->setCouponDiscounts(self::SINGLE_COUPON_CODE);
        $placeOrder->getExtensionAttributes()->setCouponCodes(array_keys(self::SINGLE_COUPON_CODE));
        $this->orderRepository->save($placeOrder);

        $placeOrder->getExtensionAttributes()->setCouponDiscounts(null);
        $placeOrder->getExtensionAttributes()->setCouponCodes(null);
        /** @var OrderInterface $order */
        $order = $this->orderRepository->get($placeOrder->getId());
        $this->assertEquals(['COUPON-C'], array_values($order->getExtensionAttributes()->getCouponCodes()));

        //Unsetting the coupon code to avoid Integrity constraint violation in PlaceOrderFixture revert method
        $placeOrder->getExtensionAttributes()->setCouponCodes([]);
        $placeOrder->getExtensionAttributes()->setCouponDiscounts([]);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2', 'store')
    ]
    public function testBeforeSaveWithMultipleCoupons()
    {
        $placeOrder = $this->fixtures->get('order');
        $placeOrder->getExtensionAttributes()->setCouponDiscounts(self::COUPON_CODES);
        $placeOrder->getExtensionAttributes()->setCouponCodes(array_keys(self::COUPON_CODES));
        $this->orderRepository->save($placeOrder);

        /** @var OrderInterface $order */
        $order = $this->orderRepository->get($placeOrder->getId());
        $this->assertEmpty($order->getCouponCode());

        //Unsetting the coupon code to avoid Integrity constraint violation in PlaceOrderFixture revert method
        $placeOrder->getExtensionAttributes()->setCouponCodes([]);
        $placeOrder->getExtensionAttributes()->setCouponDiscounts([]);
    }
}
