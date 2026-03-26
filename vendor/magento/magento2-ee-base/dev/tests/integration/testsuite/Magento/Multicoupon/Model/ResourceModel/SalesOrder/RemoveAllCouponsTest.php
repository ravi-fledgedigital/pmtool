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

namespace Magento\Multicoupon\Model\ResourceModel\SalesOrder;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Customer\Test\Fixture\Customer;
use Magento\Multicoupon\Api\SalesOrder\RemoveAllCouponsInterface;
use Magento\Multicoupon\Api\SalesOrder\GetCouponsInterface;
use Magento\Multicoupon\Test\Fixture\AddSalesOrderCoupons as AddCouponsFixture;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for SalesOrder/RemoveAllCouponsInterface
 */
class RemoveAllCouponsTest extends TestCase
{
    /** @var GetCouponsInterface  */
    private $getCoupons;

    /** @var RemoveAllCouponsInterface  */
    private $removeAllCoupons;

    private const COUPON_CODES = ['COUPON1'=>1.5, 'COUPON2'=>2.0];

    protected function setUp(): void
    {
        parent::setUp();

        $this->getCoupons = Bootstrap::getObjectManager()->get(GetCouponsInterface::class);
        $this->removeAllCoupons = Bootstrap::getObjectManager()->get(RemoveAllCouponsInterface::class);
    }

    #[
        DataFixture(
            Customer::class,
            [
                'email' => 'customer@example.com',
                'password' => 'pa55w0rd'
            ],
            'customer'
        ),
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
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(
            AddCouponsFixture::class,
            ['order_id' => '$order.id$', 'coupon_codes' => self::COUPON_CODES]
        ),
    ]
    public function testExecute()
    {
        /** @var OrderInterface $order */
        $order = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('order');
        $this->assertEquals(self::COUPON_CODES, $this->getCoupons->execute((string)$order->getEntityId()));
        $this->removeAllCoupons->execute((string)$order->getEntityId());
        $this->assertEquals([], $this->getCoupons->execute((string)$order->getEntityId()));
    }
}
