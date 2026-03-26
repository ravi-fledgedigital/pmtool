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
use Magento\Multicoupon\Api\Quote\AddCouponsInterface;
use Magento\Multicoupon\Api\Quote\GetCouponsInterface;
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
class AddCouponsTest extends TestCase
{
    /** @var AddCouponsInterface  */
    private $addCoupons;

    /** @var GetCouponsInterface */
    private $getCoupons;

    private const COUPON_CODES = ['abcd123', 'qwerty890'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->addCoupons = Bootstrap::getObjectManager()->get(AddCouponsInterface::class);
        $this->getCoupons = Bootstrap::getObjectManager()->get(GetCouponsInterface::class);
    }

    #[
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]
        ),
    ]
    public function testAddCoupons()
    {
        /** @var CartInterface $cart */
        $cart = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('cart');
        $this->addCoupons->execute((string)$cart->getId(), self::COUPON_CODES);
        $this->assertEquals(self::COUPON_CODES, $this->getCoupons->execute((string)$cart->getId()));
    }
}
