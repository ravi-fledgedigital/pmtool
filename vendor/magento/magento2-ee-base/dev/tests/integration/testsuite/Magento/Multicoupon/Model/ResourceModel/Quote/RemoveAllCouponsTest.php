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
use Magento\Multicoupon\Api\Quote\RemoveAllCouponsInterface;
use Magento\Multicoupon\Api\Quote\GetCouponsInterface;
use Magento\Multicoupon\Test\Fixture\AddQuoteCoupons as AddCouponsFixture;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for Quote/RemoveAllCouponsInterface
 */
class RemoveAllCouponsTest extends TestCase
{
    /** @var GetCouponsInterface  */
    private $getCoupons;

    /** @var RemoveAllCouponsInterface  */
    private $removeAllCoupons;

    private const COUPON_CODES = ['COUPON1', 'COUPON2'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->getCoupons = Bootstrap::getObjectManager()->get(GetCouponsInterface::class);
        $this->removeAllCoupons = Bootstrap::getObjectManager()->get(RemoveAllCouponsInterface::class);
    }

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
    public function testExecute()
    {
        /** @var CartInterface $order */
        $cart = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('cart');
        $this->assertEquals(self::COUPON_CODES, $this->getCoupons->execute((string)$cart->getId()));
        $this->removeAllCoupons->execute((string)$cart->getId());
        $this->assertEquals([], $this->getCoupons->execute((string)$cart->getId()));
    }
}
