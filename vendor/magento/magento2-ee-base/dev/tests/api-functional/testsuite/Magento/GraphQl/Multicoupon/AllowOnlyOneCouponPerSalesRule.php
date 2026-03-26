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

namespace Magento\GraphQl\Multicoupon;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Customer\Test\Fixture\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Quote\Test\Fixture\QuoteIdMask as QuoteMaskFixture;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\SalesRule\Model\Rule as SalesRule;
use Magento\SalesRule\Test\Fixture\Rule as SalesRuleFixture;
use Magento\SalesRule\Test\Fixture\RuleCoupon as RuleCouponFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQl\ResponseContainsErrorsException;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Integration test for Quote/AddCouponsInterface
 */
#[
    DataFixture(
        SalesRuleFixture::class,
        [
            'store_labels' => [1 => 'Another Test Coupon label'],
            'coupon_type' => SalesRule::COUPON_TYPE_SPECIFIC,
            'uses_per_customer' => 1,
            'discount_amount' => 10
        ],
        as: 'rule'
    ),
    DataFixture(
        RuleCouponFixture::class,
        [
            'rule_id' => '$rule.id$',
            'code' => self::COUPON_CODE_A
        ]
    ),
    DataFixture(
        RuleCouponFixture::class,
        [
            'rule_id' => '$rule.id$',
            'code' => self::COUPON_CODE_B
        ]
    ),
    DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
    DataFixture(Customer::class, as: 'customer'),
    DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
    DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1]),
    DataFixture(QuoteMaskFixture::class, ['cart_id' => '$quote.id$'], 'quoteIdMask'),
    Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2')
]
class AllowOnlyOneCouponPerSalesRule extends GraphQlAbstract
{
    private const COUPON_CODE_A = 'COUPON-MULTI-A';
    private const COUPON_CODE_B = 'COUPON-MULTI-B';

    /**
     * Set two coupon codes from the same rule
     *
     * @return void
     * @throws LocalizedException
     */
    public function testSetCoupons()
    {
        $this->expectException(ResponseContainsErrorsException::class);
        $this->expectExceptionMessage(
            'The following coupon codes could not be applied: "' . self::COUPON_CODE_B . '".'
        );

        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $query = $this->getQuery($maskedQuoteId, [self::COUPON_CODE_A, self::COUPON_CODE_B]);
        $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
    }

    /**
     * Append a coupon from the same rule as already applied to the cart
     *
     * @return void
     * @throws LocalizedException
     */
    public function testAppendCoupon()
    {
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $response = $this->graphQlMutation(
            $this->getQuery($maskedQuoteId, [self::COUPON_CODE_B]),
            [],
            '',
            $this->getCustomerHeader()
        );

        self::assertEquals(self::COUPON_CODE_B, $response['applyCouponsToCart']['cart']['applied_coupons'][0]['code']);

        $this->expectException(ResponseContainsErrorsException::class);
        $this->expectExceptionMessage(
            'The following coupon codes could not be applied: "' . self::COUPON_CODE_A . '".'
        );

        $this->graphQlMutation(
            $this->getQuery($maskedQuoteId, [self::COUPON_CODE_A], 'APPEND'),
            [],
            '',
            $this->getCustomerHeader()
        );
    }

    /**
     * Replace coupons using two coupons from the same rule
     *
     * @return void
     * @throws LocalizedException
     */
    public function testReplaceCoupons()
    {
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $response = $this->graphQlMutation(
            $this->getQuery($maskedQuoteId, [self::COUPON_CODE_A]),
            [],
            '',
            $this->getCustomerHeader()
        );

        self::assertEquals(self::COUPON_CODE_A, $response['applyCouponsToCart']['cart']['applied_coupons'][0]['code']);

        $this->expectException(ResponseContainsErrorsException::class);
        $this->expectExceptionMessage(
            'The following coupon codes could not be applied: "' . self::COUPON_CODE_A . '".'
        );

        $this->graphQlMutation(
            $this->getQuery($maskedQuoteId, [self::COUPON_CODE_B, self::COUPON_CODE_A], 'REPLACE'),
            [],
            '',
            $this->getCustomerHeader()
        );
    }

    /**
     * Retrieve GraphQL query
     *
     * @param string $maskedQuoteId
     * @param array $couponCodes
     * @param string $type
     * @return string
     */
    private function getQuery(string $maskedQuoteId = '', array $couponCodes = [], string $type = ''): string
    {
        $codes = '';
        if ($couponCodes) {
            $codes = 'coupon_codes: ["' . implode('","', $couponCodes) . '"],';
        }

        if ($type) {
            $type = ",type: $type";
        }

        return <<<QUERY
mutation {
  applyCouponsToCart(input: {cart_id: "$maskedQuoteId", $codes $type}) {
    cart {
      applied_coupons {
        code
      }
      prices {
        grand_total {
          value
        }
        discounts {
          amount {
            value
          }
        }
      }
    }
  }
}
QUERY;
    }

    /**
     * Retrieve customer header for GraphQL request
     *
     * @param string $customerFixtureName
     * @return array
     * @throws LocalizedException
     */
    private function getCustomerHeader(string $customerFixtureName = 'customer'): array
    {
        return Bootstrap::getObjectManager()
            ->get(GetCustomerAuthenticationHeader::class)
            ->execute(
                DataFixtureStorageManager::getStorage()->get($customerFixtureName)->getEmail()
            );
    }
}
