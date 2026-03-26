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

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Multicoupon\Test\Fixture\ApplyCoupon as ApplyCouponsFixture;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\SalesRule\Test\Fixture\Rule as SalesRuleFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\Quote\Test\Fixture\QuoteIdMask as QuoteIdMaskFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Exception;

class RemoveCouponFromCartTest extends GraphQlAbstract
{
    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    private const COUPON_CODE_A = 'COUPON-A';
    private const COUPON_CODE_B = 'COUPON-B';
    private const COUPON_CODES = ['COUPON-A', 'COUPON-B'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage();
        $this->quoteRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
    }

    #[
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(ProductFixture::class, as: 'p2'),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(QuoteIdMaskFixture::class, ['cart_id' => '$cart.id$'], as: 'mask'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$p1.id$', 'qty' => 2]),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$p2.id$', 'qty' => 2]),
        DataFixture(
            SalesRuleFixture::class,
            ['coupon_code' => self::COUPON_CODE_A, 'uses_per_customer' => 1, 'discount_amount' => 10]
        ),
        DataFixture(
            SalesRuleFixture::class,
            ['coupon_code' => self::COUPON_CODE_B, 'uses_per_customer' => 1, 'discount_amount' => 10]
        ),
        DataFixture(
            ApplyCouponsFixture::class,
            ['cart_id' => '$cart.id$', 'coupon_codes' => self::COUPON_CODES],
            'cart'
        ),
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2')
    ]
    public function testRemoveSingleCoupon(): void
    {
        $maskedQuoteId = $this->fixtures->get('mask')->getMaskedId();
        $cart = $this->fixtures->get('cart');
        /** @var CartInterface $quote */
        $quote = $this->quoteRepository->get($cart->getId());
        $quote->getExtensionAttributes()->setCouponCodes(self::COUPON_CODES);
        $this->quoteRepository->save($quote->collectTotals());
        $appliedCouponCodes = $quote->getExtensionAttributes()->getCouponCodes();
        $this->assertEquals(self::COUPON_CODES, array_values($appliedCouponCodes));
        $query = $this->getQuery($maskedQuoteId, [self::COUPON_CODE_A]);
        $response = $this->graphQlMutation($query);
        $this->assertEquals(
            self::COUPON_CODE_B,
            $response['removeCouponsFromCart']['cart']['applied_coupons'][0]['code']
        );
    }

    #[
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(ProductFixture::class, as: 'p2'),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(QuoteIdMaskFixture::class, ['cart_id' => '$cart.id$'], as: 'mask'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$p1.id$', 'qty' => 2]),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$p2.id$', 'qty' => 2]),
        DataFixture(
            SalesRuleFixture::class,
            ['coupon_code' => self::COUPON_CODE_A, 'uses_per_customer' => 1, 'discount_amount' => 10]
        ),
        DataFixture(
            SalesRuleFixture::class,
            ['coupon_code' => self::COUPON_CODE_B, 'uses_per_customer' => 1, 'discount_amount' => 10]
        ),
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2')
    ]
    public function testRemoveAllCoupon(): void
    {
        $maskedQuoteId = $this->fixtures->get('mask')->getMaskedId();
        $cart = $this->fixtures->get('cart');
        /** @var CartInterface $quote */
        $quote = $this->quoteRepository->get($cart->getId());
        $quote->getExtensionAttributes()->setCouponCodes(self::COUPON_CODES);
        $this->quoteRepository->save($quote->collectTotals());
        $appliedCouponCodes = $quote->getExtensionAttributes()->getCouponCodes();
        $this->assertEquals(self::COUPON_CODES, array_values($appliedCouponCodes));
        $query = $this->getQuery($maskedQuoteId, []);
        $response = $this->graphQlMutation($query);
        $this->assertEquals(null, $response['removeCouponsFromCart']['cart']['applied_coupons']);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2')
    ]
    public function testCartIdMissing()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Required parameter "cart_id" is missing.');
        $query = $this->getQuery('', [self::COUPON_CODE_B]);
        $this->graphQlMutation($query);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2')
    ]
    public function testWrongCartId(): void
    {
        $this->expectException(Exception::class);
        $maskedQuoteId = "abc12345abc";
        $this->expectExceptionMessage(sprintf('Could not find a cart with ID "%s"', $maskedQuoteId));
        $this->graphQlMutation($this->getQuery($maskedQuoteId, []));
    }

    /**
     * Returns GraphQl mutation string
     *
     * @param string $maskedQuoteId
     * @param array $couponCodes
     * @return string
     */
    private function getQuery(string $maskedQuoteId = '', array $couponCodes = []): string
    {
        $code = 'coupon_codes: [' . (!empty($couponCodes) ? '"' . implode('","', $couponCodes) . '"' : '') . ']';
        return <<<QUERY
mutation {
  removeCouponsFromCart(input: {cart_id: "$maskedQuoteId", $code}) {
    cart {
      applied_coupons {
        code
      }
    }
  }
}
QUERY;
    }
}
