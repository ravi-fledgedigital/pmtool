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

namespace Magento\Multicoupon\Model\ResourceModel\Quote;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Multicoupon\Api\Quote\GetCouponsInterface;
use Magento\Multicoupon\Api\Quote\RemoveCouponsInterface;
use Magento\Multicoupon\Test\Fixture\AddQuoteCoupons as AddCouponsFixture;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for Quote/AddCouponsInterface
 */
#[
    DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
    DataFixture(GuestCartFixture::class, as: 'cart'),
    DataFixture(
        AddProductToCartFixture::class,
        ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]
    ),
    DataFixture(
        AddCouponsFixture::class,
        ['cart_id' => '$cart.id$', 'coupon_codes' => self::COUPON_CODES]
    ),
]
class RemoveCouponsTest extends TestCase
{
    /** @var CartInterface  */
    private $cart;

    /** @var GetCouponsInterface  */
    private $getCoupons;

    /** @var RemoveCouponsInterface */
    private $removeCoupons;

    private const COUPON_CODES = ['COUPON1', 'COUPON2'];

    private const REMOVE_COUPON_CODES = ['COUPON1'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->getCoupons = Bootstrap::getObjectManager()->get(GetCouponsInterface::class);
        $this->removeCoupons = Bootstrap::getObjectManager()->get(RemoveCouponsInterface::class);
        $this->cart = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('cart');
    }

    public function testRemoveCoupons()
    {
        $this->assertEquals(self::COUPON_CODES, $this->getCoupons->execute((string)$this->cart->getId()));
        $this->removeCoupons->execute((string)$this->cart->getId(), self::REMOVE_COUPON_CODES);
        $this->assertEquals(['COUPON2'], $this->getCoupons->execute((string)$this->cart->getId()));
    }

    public function testRemoveAllCoupons()
    {
        $this->assertEquals(self::COUPON_CODES, $this->getCoupons->execute((string)$this->cart->getEntityId()));
        $this->removeCoupons->execute((string)$this->cart->getEntityId(), self::COUPON_CODES);
        $this->assertEquals([], $this->getCoupons->execute((string)$this->cart->getEntityId()));
    }

    public function testRemoveNonExistentCoupon()
    {
        $this->assertEquals(self::COUPON_CODES, $this->getCoupons->execute((string)$this->cart->getEntityId()));
        $this->removeCoupons->execute((string)$this->cart->getEntityId(), ['W0rn9C0up0n']);
        $this->assertEquals(self::COUPON_CODES, $this->getCoupons->execute((string)$this->cart->getEntityId()));
    }

    /**
     * @dataProvider couponsProvider
     */
    public function testRemoveCouponsSqlInjection(string $cartId, string $coupon, array $expectedCoupons)
    {
        if (empty($cartId)) {
            $cartId = (string)$this->cart->getEntityId();
        }
        $this->assertEquals(self::COUPON_CODES, $this->getCoupons->execute((string)$this->cart->getEntityId()));
        $this->removeCoupons->execute($cartId, [$coupon, 'COUPON2']);
        $this->assertEquals($expectedCoupons, $this->getCoupons->execute((string)$this->cart->getEntityId()));
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
                ['COUPON1']
            ],
            [
                '',
                "'COUPON1",
                ['COUPON1']
            ],
            [
                '',
                '1 OR 1=1',
                ['COUPON1']
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
