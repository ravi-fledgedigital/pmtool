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
use Magento\Multicoupon\Api\Quote\ReplaceCouponsInterface;
use Magento\Multicoupon\Test\Fixture\AddQuoteCoupons as AddCouponsFixture;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for SalesOrder/ReplaceCouponsInterface
 */
#[
    DataFixture(ProductFixture::class, as: 'product'),
    DataFixture(GuestCartFixture::class, as: 'cart'),
    DataFixture(
        AddProductToCartFixture::class,
        ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 2]
    ),
    DataFixture(
        AddCouponsFixture::class,
        ['cart_id' => '$cart.id$', 'coupon_codes' => self::COUPON_CODES]
    ),
]
class ReplaceCouponsTest extends TestCase
{
    private const COUPON_CODES = ['COUPON1', 'COUPON2'];

    private const NEW_COUPON_CODES = ['COUPON3', 'COUPON4'];

    /**
     * @var \Magento\Multicoupon\Api\Quote\DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var GetCouponsInterface
     */
    private $getCoupons;

    /**
     * @var ReplaceCouponsInterface
     */
    private $replaceCoupons;

    protected function setUp(): void
    {
        $this->fixtures = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage();
        $this->getCoupons = Bootstrap::getObjectManager()->get(GetCouponsInterface::class);
        $this->replaceCoupons = Bootstrap::getObjectManager()->get(ReplaceCouponsInterface::class);
    }

    public function testReplaceCoupons()
    {
        $quote = $this->fixtures->get('cart');
        $this->assertEquals(self::COUPON_CODES, $this->getCoupons->execute($quote->getId()));
        $this->replaceCoupons->execute($quote->getId(), self::NEW_COUPON_CODES);
        $result = $this->getCoupons->execute($quote->getId());
        $this->assertIsArray($result);
        $this->assertEquals(self::NEW_COUPON_CODES, $result);
    }

    public function testReplaceCouponsWithEmptyArray()
    {
        $quote = $this->fixtures->get('cart');
        $this->assertEquals(self::COUPON_CODES, $this->getCoupons->execute($quote->getId()));
        $this->replaceCoupons->execute($quote->getId(), []);
        $result = $this->getCoupons->execute($quote->getId());
        $this->assertIsArray($result);
        $this->assertEquals([], $result);
    }
}
