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

use Exception;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Customer\Test\Fixture\Customer;
use Magento\Framework\DataObject;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Quote\Test\Fixture\QuoteIdMask as QuoteMaskFixture;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\SalesRule\Model\Rule as SalesRule;
use Magento\SalesRule\Test\Fixture\Rule as SalesRuleFixture;
use Magento\SalesRule\Test\Fixture\RuleCoupon as RuleCouponFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQl\ResponseContainsErrorsException;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class MultiCouponDiscountTest extends GraphQlAbstract
{
    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var GetCustomerHeaders
     */
    private $getCustomerHeaders;

    private const COUPON_CODE_A = 'COUPON-AD-123';
    private const COUPON_CODE_C = 'COUPON-CD-456';
    private const COUPON_CODE_SR1 = 'COUPON-SR-123';
    private const COUPON_CODE_SR2 = 'COUPON-SR-456';
    private const COUPON_CODE_AR = 'COUPON-AR-123';
    private const COUPON_CODE_BR = 'COUPON-BR-234';
    private const COUPON_CODE_BP1 = 'COUPON-BP1-123';
    private const COUPON_CODE_BP2 = 'COUPON-BP2-123';
    private const COUPON_CODES = [['code'=>'COUPON-AD-123'], ['code'=>'COUPON-CD-456']];

    protected function setUp(): void
    {
        parent::setUp();
        $this->getCustomerHeaders = Bootstrap::getObjectManager()->get(GetCustomerAuthenticationHeader::class);
        $this->fixtures = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage();
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2'),
        DataFixture(
            SalesRuleFixture::class,
            [
                'store_labels' => [1 => 'Coupon1'],
                'coupon_type' => SalesRule::COUPON_TYPE_SPECIFIC,
                'simple_action' => SalesRule::BY_PERCENT_ACTION,
                'uses_per_customer' => 1,
                'discount_amount' => 10,
                'stop_rules_processing' => false
            ],
            as: 'rule1'
        ),
        DataFixture(
            SalesRuleFixture::class,
            [
                'store_labels' => [1 => 'Coupon3'],
                'coupon_type' => SalesRule::COUPON_TYPE_SPECIFIC,
                'simple_action' => SalesRule::BY_PERCENT_ACTION,
                'uses_per_customer' => 1,
                'discount_amount' => 5,
                'stop_rules_processing' => true
            ],
            as: 'rule3'
        ),
        DataFixture(
            RuleCouponFixture::class,
            [
                'rule_id' => '$rule1.id$',
                'code' => self::COUPON_CODE_A
            ]
        ),
        DataFixture(
            RuleCouponFixture::class,
            [
                'rule_id' => '$rule3.id$',
                'code' => self::COUPON_CODE_C
            ]
        ),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1]),
        DataFixture(QuoteMaskFixture::class, ['cart_id' => '$quote.id$'], 'quoteIdMask'),
    ]
    public function testApplyDiscountCoupon()
    {
        $maskedQuoteId = $this->fixtures->get('quoteIdMask')->getMaskedId();
        $cart = $this->fixtures->get('quote');

        self::assertEquals(100, $cart->getGrandTotal());
        // Apply 10% discount coupon
        $query = $this->getApplyDiscountQuery($maskedQuoteId, [self::COUPON_CODE_A], 'APPEND');
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());

        self::assertArrayHasKey('applyCouponsToCart', $response);
        self::assertEquals(90, $response['applyCouponsToCart']['cart']['prices']['grand_total']['value']);
        self::assertEquals(self::COUPON_CODE_A, $response['applyCouponsToCart']['cart']['applied_coupons'][0]['code']);

        // Append  5% discount coupon
        $query = $this->getApplyDiscountQuery($maskedQuoteId, [self::COUPON_CODE_C], 'APPEND');
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());

        self::assertEquals(85.5, $response['applyCouponsToCart']['cart']['prices']['grand_total']['value']);
        self::assertEquals(self::COUPON_CODES, $response['applyCouponsToCart']['cart']['applied_coupons']);

        // Test cart query with multiple discount coupons
        $query = $this->getCartQueryWithDiscounts($maskedQuoteId);
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
        $responseDataObject = new DataObject($response);
        $discounts = $responseDataObject->getData('cart/prices/discounts');
        self::assertEquals(
            $discounts,
            [
                ['amount'=>['value'=>10],'label'=>'Coupon1'],
                ['amount'=>['value'=>4.5],'label'=>'Coupon3']
            ]
        );
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2'),
        DataFixture(
            SalesRuleFixture::class,
            [
                'store_labels' => [1 => 'CouponS1'],
                'coupon_type' => SalesRule::COUPON_TYPE_SPECIFIC,
                'simple_action' => SalesRule::BY_PERCENT_ACTION,
                'uses_per_customer' => 1,
                'discount_amount' => 10,
                'stop_rules_processing' => true
            ],
            as: 'rule1'
        ),
        DataFixture(
            SalesRuleFixture::class,
            [
                'store_labels' => [1 => 'CouponS2'],
                'coupon_type' => SalesRule::COUPON_TYPE_SPECIFIC,
                'simple_action' => SalesRule::BY_PERCENT_ACTION,
                'uses_per_customer' => 1,
                'sort_order' => 2,
                'discount_amount' => 5,
                'stop_rules_processing' => true
            ],
            as: 'rule2'
        ),
        DataFixture(
            RuleCouponFixture::class,
            [
                'rule_id' => '$rule1.id$',
                'code' => self::COUPON_CODE_SR1
            ]
        ),
        DataFixture(
            RuleCouponFixture::class,
            [
                'rule_id' => '$rule2.id$',
                'code' => self::COUPON_CODE_SR2
            ]
        ),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1]),
        DataFixture(QuoteMaskFixture::class, ['cart_id' => '$quote.id$'], 'quoteIdMask'),
    ]
    public function testStopRulesProcessingCoupon()
    {
        $this->expectException(ResponseContainsErrorsException::class);
        $this->expectExceptionMessage(
            'The following coupon codes could not be applied: "' . self::COUPON_CODE_SR2 . '".'
        );

        $maskedQuoteId = $this->fixtures->get('quoteIdMask')->getMaskedId();
        $cart = $this->fixtures->get('quote');

        self::assertEquals(100, $cart->getGrandTotal());
        // Apply 10% discount coupon
        $query = $this->getApplyDiscountQuery($maskedQuoteId, [self::COUPON_CODE_SR1, self::COUPON_CODE_SR2], 'APPEND');
        $this->graphQlMutation($query, [], '', $this->getCustomerHeader());

        // Test cart query with multiple discount coupons
        $query = $this->getCartQueryWithDiscounts($maskedQuoteId);
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());

        $responseDataObject = new DataObject($response);
        $discounts = $responseDataObject->getData('cart/prices/discounts');
        self::assertEquals(
            $discounts,
            [
                ['amount'=>['value'=>10],'label'=>'CouponS1']
            ]
        );
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '3'),
        DataFixture(
            SalesRuleFixture::class,
            [
                'store_labels' => [1 => 'Coupon10'],
                'coupon_type' => SalesRule::COUPON_TYPE_SPECIFIC,
                'simple_action' => SalesRule::BY_PERCENT_ACTION,
                'uses_per_customer' => 1,
                'discount_amount' => 10,
                'stop_rules_processing' => false
            ],
            as: 'rule1'
        ),
        DataFixture(
            SalesRuleFixture::class,
            [
                'store_labels' => [1 => 'Coupon20'],
                'coupon_type' => SalesRule::COUPON_TYPE_SPECIFIC,
                'simple_action' => SalesRule::BY_PERCENT_ACTION,
                'uses_per_customer' => 1,
                'discount_amount' => 20,
                'stop_rules_processing' => true
            ],
            as: 'rule2'
        ),
        DataFixture(
            RuleCouponFixture::class,
            [
                'rule_id' => '$rule1.id$',
                'code' => self::COUPON_CODE_AR
            ]
        ),
        DataFixture(
            RuleCouponFixture::class,
            [
                'rule_id' => '$rule2.id$',
                'code' => self::COUPON_CODE_BR
            ]
        ),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1]),
        DataFixture(QuoteMaskFixture::class, ['cart_id' => '$quote.id$'], 'quoteIdMask'),
    ]
    public function testRemoveDiscountCoupon()
    {
        $maskedQuoteId = $this->fixtures->get('quoteIdMask')->getMaskedId();
        $cart = $this->fixtures->get('quote');

        self::assertEquals(100, $cart->getGrandTotal());

        // Apply 10%  discount coupon
        $query = $this->getApplyDiscountQuery($maskedQuoteId, [self::COUPON_CODE_AR], 'APPEND');
        $this->graphQlMutation($query, [], '', $this->getCustomerHeader());

        // Apply 20% discount coupon
        $query = $this->getApplyDiscountQuery($maskedQuoteId, [self::COUPON_CODE_BR], 'APPEND');
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());

        self::assertArrayHasKey('applyCouponsToCart', $response);
        self::assertEquals(72, $response['applyCouponsToCart']['cart']['prices']['grand_total']['value']);
        self::assertEquals(self::COUPON_CODE_AR, $response['applyCouponsToCart']['cart']['applied_coupons'][0]['code']);
        self::assertEquals(self::COUPON_CODE_BR, $response['applyCouponsToCart']['cart']['applied_coupons'][1]['code']);

        // Remove COUPON-A
        $query = $this->getRemoveDiscountQuery($maskedQuoteId, [self::COUPON_CODE_AR]);
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());

        self::assertEquals(80, $response['removeCouponsFromCart']['cart']['prices']['grand_total']['value']);
        self::assertEquals(
            self::COUPON_CODE_BR,
            $response['removeCouponsFromCart']['cart']['applied_coupons'][0]['code']
        );

        // Test cart query after remove a coupon
        $query = $this->getCartQueryWithDiscounts($maskedQuoteId);
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
        $responseDataObject = new DataObject($response);
        $discounts = $responseDataObject->getData('cart/prices/discounts');
        self::assertEquals($discounts, [['amount'=>['value'=>20],'label'=>'Coupon20']]);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2'),
        DataFixture(
            SalesRuleFixture::class,
            [
                'store_labels' => [1 => 'Test3-10'],
                'coupon_type' => SalesRule::COUPON_TYPE_SPECIFIC,
                'simple_action' => SalesRule::BY_PERCENT_ACTION,
                'uses_per_customer' => 1,
                'discount_amount' => 10,
                'stop_rules_processing' => false
            ],
            as: 'rule1'
        ),
        DataFixture(
            SalesRuleFixture::class,
            [
                'store_labels' => [1 => 'Test3-20'],
                'coupon_type' => SalesRule::COUPON_TYPE_SPECIFIC,
                'simple_action' => SalesRule::TO_PERCENT_ACTION,
                'uses_per_customer' => 1,
                'discount_amount' => 10
            ],
            as: 'rule4'
        ),
        DataFixture(
            RuleCouponFixture::class,
            [
                'rule_id' => '$rule1.id$',
                'code' => self::COUPON_CODE_BP1
            ]
        ),
        DataFixture(
            RuleCouponFixture::class,
            [
                'rule_id' => '$rule4.id$',
                'code' => self::COUPON_CODE_BP2
            ]
        ),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1]),
        DataFixture(QuoteMaskFixture::class, ['cart_id' => '$quote.id$'], 'quoteIdMask'),
    ]
    public function testApplyFixedDiscountCoupon()
    {
        $maskedQuoteId = $this->fixtures->get('quoteIdMask')->getMaskedId();
        $cart = $this->fixtures->get('quote');
        self::assertEquals(100, $cart->getGrandTotal());
        $query = $this->getApplyDiscountQuery($maskedQuoteId, [self::COUPON_CODE_BP1]);
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
        self::assertArrayHasKey('applyCouponsToCart', $response);
        self::assertEquals(90, $response['applyCouponsToCart']['cart']['prices']['grand_total']['value']);
        self::assertEquals(
            self::COUPON_CODE_BP1,
            $response['applyCouponsToCart']['cart']['applied_coupons'][0]['code']
        );

        $query = $this->getApplyDiscountQuery($maskedQuoteId, [self::COUPON_CODE_BP2], 'APPEND');
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
        self::assertEquals(9, $response['applyCouponsToCart']['cart']['prices']['grand_total']['value']);
    }

    /**
     * @param string $maskedQuoteId
     * @param string $couponCode
     * @return string
     */
    private function getApplyDiscountQuery(
        string $maskedQuoteId = '',
        array $couponCodes = [],
        string $type = ''
    ): string {
        $codes = "";
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

    private function getRemoveDiscountQuery(string $maskedQuoteId = '', array $couponCodes = []): string
    {
        $code = "";
        if ($couponCodes) {
            $code = 'coupon_codes: ["' . implode('","', $couponCodes) . '"]';
        }
        return <<<QUERY
mutation {
  removeCouponsFromCart(input: {cart_id: "$maskedQuoteId", $code}) {
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
     * @param string $maskedQuoteId
     * @return string
     */
    private function getCartQueryWithDiscounts(string $maskedQuoteId): string
    {
        return <<<QUERY
{
  cart(cart_id: "$maskedQuoteId") {
    email
    items {
      uid
      prices {
        discounts {
          amount {
            value
          }
        }
      }
      product {
        sku
      }
    }
    applied_coupons {
      code
    }
    prices {
      discounts {
        amount {
          value
        }
        label
      }
      grand_total {
        value
      }
    }
  }
}
QUERY;
    }

    /**
     * @param string $customerFixtureName
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCustomerHeader(string $customerFixtureName = 'customer'): array
    {
        $customer = DataFixtureStorageManager::getStorage()->get($customerFixtureName);
        return $this->getCustomerHeaders->execute($customer->getEmail());
    }
}
