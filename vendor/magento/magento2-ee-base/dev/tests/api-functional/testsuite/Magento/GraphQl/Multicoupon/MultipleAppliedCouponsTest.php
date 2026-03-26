<?php
/************************************************************************
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\GraphQl\Multicoupon;

use Exception;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Multicoupon\Test\Fixture\AddQuoteCoupons as AddCouponsFixture;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\Quote\Test\Fixture\QuoteIdMask as QuoteIdMaskFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test coverage for multiple applied coupon cart
 */
class MultipleAppliedCouponsTest extends GraphQlAbstract
{
    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    private const COUPON_CODES = ['COUPON-A', 'COUPON-B'];

    protected function setUp(): void
    {
        $this->fixtures = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage();
    }

    #[
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(ProductFixture::class, as: 'p2'),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(QuoteIdMaskFixture::class, ['cart_id' => '$cart.id$'], as: 'mask'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$p1.id$', 'qty' => 2]),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$p2.id$', 'qty' => 2]),
        DataFixture(
            AddCouponsFixture::class,
            ['cart_id' => '$cart.id$', 'coupon_codes' => self::COUPON_CODES]
        ),
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2')
    ]
    public function testCartWithMultipleCoupon(): void
    {
        $maskedQuoteId = $this->fixtures->get('mask')->getMaskedId();
        $query = $this->getQuery($maskedQuoteId);
        $response = $this->graphQlMutation($query);
        $this->assertArrayHasKey('applied_coupons', $response['cart']);
        $this->assertEquals('COUPON-A', $response['cart']['applied_coupons'][0]['code']);
        $this->assertEquals('COUPON-B', $response['cart']['applied_coupons'][1]['code']);
    }

    #[
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(QuoteIdMaskFixture::class, ['cart_id' => '$cart.id$'], as: 'mask'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$p1.id$', 'qty' => 2]),
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2')
    ]
    public function testCartWithOutCoupon(): void
    {
        $maskedQuoteId = $this->fixtures->get('mask')->getMaskedId();
        $query = $this->getQuery($maskedQuoteId);
        $response = $this->graphQlMutation($query);
        $this->assertEmpty($response['cart']['applied_coupons']);
    }

    public function testCartWithWrongCartId(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Could not find a cart with ID "abcdefg"'
        );

        $maskedQuoteId = "abcdefg";
        $query = $this->getQuery($maskedQuoteId);
        $this->graphQlMutation($query);
    }

    /**
     * Returns GraphQl mutation string
     *
     * @param string $cartId
     *
     * @return string
     */
    private function getQuery(
        string $cartId
    ): string {
        return <<<QUERY
query {
  cart(cart_id: "{$cartId}") {
    email
    applied_coupons {
      code
    }
  }
}
QUERY;
    }
}
