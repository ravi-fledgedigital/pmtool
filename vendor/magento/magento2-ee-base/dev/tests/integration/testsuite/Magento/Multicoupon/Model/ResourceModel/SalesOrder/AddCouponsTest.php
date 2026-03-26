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
use Magento\Multicoupon\Api\SalesOrder\AddCouponsInterface;
use Magento\Multicoupon\Api\SalesOrder\GetCouponsInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for SalesOrder/AddCouponsInterface
 */
class AddCouponsTest extends TestCase
{
    /** @var AddCouponsInterface  */
    private $addCoupons;

    /** @var GetCouponsInterface  */
    private $getCoupons;

    private const COUPON_CODES = ['abcd123'=>1.5, 'qwerty890'=>2.5];

    protected function setUp(): void
    {
        parent::setUp();

        $this->addCoupons = Bootstrap::getObjectManager()->get(AddCouponsInterface::class);
        $this->getCoupons = Bootstrap::getObjectManager()->get(GetCouponsInterface::class);
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
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order')
    ]
    public function testAddCoupons()
    {
        /** @var OrderInterface $cart */
        $order = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('order');
        $this->addCoupons->execute((string)$order->getEntityId(), self::COUPON_CODES);
        $this->assertEquals(self::COUPON_CODES, $this->getCoupons->execute((string)$order->getEntityId()));
    }
}
