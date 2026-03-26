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
use Magento\Multicoupon\Api\SalesOrder\RemoveCouponsInterface;
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
 * Integration test for SalesOrder/RemoveCouponsInterface
 */
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
class RemoveCouponsTest extends TestCase
{

    /** @var GetCouponsInterface  */
    private $getCoupons;

    /** @var RemoveCouponsInterface  */
    private $removeCoupons;

    /** @var OrderInterface $order */
    private $order;

    private const COUPON_CODES = ['COUPON1'=>1.5, 'COUPON2'=>2.0];

    private const REMOVE_COUPON_CODES = ['COUPON1'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->getCoupons = Bootstrap::getObjectManager()->get(GetCouponsInterface::class);
        $this->removeCoupons = Bootstrap::getObjectManager()->get(RemoveCouponsInterface::class);
        $this->order = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('order');
    }

    public function testRemoveCoupon()
    {
        $this->assertEquals(self::COUPON_CODES, $this->getCoupons->execute((string)$this->order->getEntityId()));
        $this->removeCoupons->execute((string)$this->order->getEntityId(), self::REMOVE_COUPON_CODES);
        $this->assertEquals(['COUPON2'=>2.0], $this->getCoupons->execute((string)$this->order->getEntityId()));
    }

    public function testRemoveAllCoupons()
    {
        $this->assertEquals(self::COUPON_CODES, $this->getCoupons->execute((string)$this->order->getEntityId()));
        $this->removeCoupons->execute((string)$this->order->getEntityId(), array_keys(self::COUPON_CODES));
        $this->assertEquals([], $this->getCoupons->execute((string)$this->order->getEntityId()));
    }

    public function testRemoveNonExistentCoupon()
    {
        $this->assertEquals(self::COUPON_CODES, $this->getCoupons->execute((string)$this->order->getEntityId()));
        $this->removeCoupons->execute((string)$this->order->getEntityId(), ['W0rn9C0up0n']);
        $this->assertEquals(self::COUPON_CODES, $this->getCoupons->execute((string)$this->order->getEntityId()));
    }

    /**
     * @dataProvider couponsProvider
     */
    public function testRemoveCouponsSqlInjection(string $order_id, string $coupon, array $expectedCoupons)
    {
        if (empty($order_id)) {
            $order_id = (string)$this->order->getEntityId();
        }
        $this->assertEquals(self::COUPON_CODES, $this->getCoupons->execute((string)$this->order->getEntityId()));
        $this->removeCoupons->execute($order_id, [$coupon, 'COUPON2']);
        $this->assertEquals($expectedCoupons, $this->getCoupons->execute((string)$this->order->getEntityId()));
    }

    /**
     * @return array[]
     */
    public function couponsProvider(): array
    {
        return [
            [
                '',
                ';COUPON1',
                ['COUPON1'=>1.5]
            ],
            [
                '',
                "'COUPON1",
                ['COUPON1'=>1.5]
            ],
            [
                '',
                '1 OR 1=1',
                ['COUPON1'=>1.5]
            ],
            [
                '2 OR 1=1',
                'COUPON1',
                self::COUPON_CODES
            ],
            [
                ';1',
                'COUPON1',
                self::COUPON_CODES
            ],
            [
                "'1",
                'COUPON1',
                self::COUPON_CODES
            ]
        ];
    }
}
