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
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Multicoupon\Api\Quote\AddCouponsInterface;
use Magento\Quote\Test\Fixture\QuoteIdMask as QuoteMaskFixture;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\SalesRule\Test\Fixture\Rule as SalesRuleFixture;
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
            'coupon_code' => 'coupon%uniqid%',
            'discount_amount' => 10,
            'stop_rules_processing' => false
        ],
        'rule1'
    ),
    DataFixture(
        SalesRuleFixture::class,
        [
            'coupon_code' => 'coupon%uniqid%',
            'discount_amount' => 10,
            'stop_rules_processing' => false
        ],
        'rule2'
    ),
    DataFixture(
        SalesRuleFixture::class,
        [
            'coupon_code' => 'coupon%uniqid%',
            'discount_amount' => 10,
            'stop_rules_processing' => false
        ],
        'rule3'
    ),
    DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
    DataFixture(Customer::class, as: 'customer'),
    DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
    DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1]),
    DataFixture(QuoteMaskFixture::class, ['cart_id' => '$quote.id$'], 'quoteIdMask'),
]
class ApplyCouponToCartTest extends GraphQlAbstract
{
    /**
     * @var AddCouponsInterface
     */
    private $addCoupons;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerHeaders;

    private const COUPON_CODE_INVALID = 'hncprqx';

    protected function setUp(): void
    {
        parent::setUp();

        $this->addCoupons = Bootstrap::getObjectManager()->get(AddCouponsInterface::class);
        $this->getCustomerHeaders = Bootstrap::getObjectManager()->get(GetCustomerAuthenticationHeader::class);
    }

    public function testApplyCoupon()
    {
        $coupon1 = DataFixtureStorageManager::getStorage()->get('rule1')->getData('coupon_code');
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $query = $this->getQuery($maskedQuoteId, [$coupon1]);
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());

        self::assertArrayHasKey('applyCouponsToCart', $response);
        self::assertEquals($coupon1, $response['applyCouponsToCart']['cart']['applied_coupons'][0]['code']);
    }

    public function testReplaceCoupon()
    {
        $coupon1 = DataFixtureStorageManager::getStorage()->get('rule1')->getData('coupon_code');
        $coupon2 = DataFixtureStorageManager::getStorage()->get('rule2')->getData('coupon_code');
        $cart = DataFixtureStorageManager::getStorage()->get('quote');
        $this->addCoupons->execute((string)$cart->getId(), [$coupon2]);

        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $query = $this->getQuery($maskedQuoteId, [$coupon1], 'REPLACE');
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());

        self::assertArrayHasKey('applyCouponsToCart', $response);
        self::assertEquals($coupon1, $response['applyCouponsToCart']['cart']['applied_coupons'][0]['code']);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2')
    ]
    public function testAppendCoupon()
    {
        $coupon1 = DataFixtureStorageManager::getStorage()->get('rule1')->getData('coupon_code');
        $coupon2 = DataFixtureStorageManager::getStorage()->get('rule2')->getData('coupon_code');
        $cart = DataFixtureStorageManager::getStorage()->get('quote');
        $this->addCoupons->execute((string)$cart->getId(), [$coupon2]);

        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $query = $this->getQuery($maskedQuoteId, [$coupon1], 'APPEND');
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());

        self::assertArrayHasKey('applyCouponsToCart', $response);
        self::assertEquals(
            [
                0 => [
                    'code' => $coupon1
                ],
                1 => [
                    'code' => $coupon2
                ]
            ],
            $response['applyCouponsToCart']['cart']['applied_coupons']
        );
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2')
    ]
    public function testExceededCouponCountOnReplace()
    {
        $this->expectException(ResponseContainsErrorsException::class);
        $this->expectExceptionMessage('Maximum allowed number of applied coupons was exceeded.');

        $coupon1 = DataFixtureStorageManager::getStorage()->get('rule1')->getData('coupon_code');
        $coupon2 = DataFixtureStorageManager::getStorage()->get('rule2')->getData('coupon_code');
        $coupon3 = DataFixtureStorageManager::getStorage()->get('rule3')->getData('coupon_code');
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $query = $this->getQuery($maskedQuoteId, [$coupon1, $coupon2, $coupon3]);
        $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2')
    ]
    public function testExceededCouponCountOnAppend()
    {
        $coupon1 = DataFixtureStorageManager::getStorage()->get('rule1')->getData('coupon_code');
        $coupon2 = DataFixtureStorageManager::getStorage()->get('rule2')->getData('coupon_code');
        $coupon3 = DataFixtureStorageManager::getStorage()->get('rule3')->getData('coupon_code');
        $this->expectException(ResponseContainsErrorsException::class);
        $this->expectExceptionMessage('Maximum allowed number of applied coupons was exceeded.');

        $cart = DataFixtureStorageManager::getStorage()->get('quote');
        $this->addCoupons->execute((string)$cart->getId(), [$coupon1, $coupon2]);

        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $query = $this->getQuery($maskedQuoteId, [$coupon3], 'APPEND');
        $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
    }

    public function testCartIdMissing()
    {
        $coupon2 = DataFixtureStorageManager::getStorage()->get('rule2')->getData('coupon_code');
        $this->expectException(ResponseContainsErrorsException::class);
        $this->expectExceptionMessage('Required parameter "cart_id" is missing.');

        $query = $this->getQuery('', [$coupon2]);
        $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
    }

    public function testCouponCodeMissing()
    {
        $this->expectException(ResponseContainsErrorsException::class);
        $this->expectExceptionMessage(
            'Field ApplyCouponsToCartInput.coupon_codes of required type [String]! was not provided.'
        );

        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $query = $this->getQuery($maskedQuoteId);
        $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
    }

    public function testInvalidTypeParam()
    {
        $this->expectException(ResponseContainsErrorsException::class);
        $this->expectExceptionMessage('Value "something" does not exist in "ApplyCouponsStrategy" enum.');

        $coupon1 = DataFixtureStorageManager::getStorage()->get('rule1')->getData('coupon_code');
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $query = $this->getQuery($maskedQuoteId, [$coupon1], 'something');
        $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2')
    ]
    public function testInvalidCoupon()
    {
        $this->expectException(ResponseContainsErrorsException::class);
        $this->expectExceptionMessage(
            'The following coupon codes could not be applied: "' . self::COUPON_CODE_INVALID . '".'
        );

        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $query = $this->getQuery($maskedQuoteId, [self::COUPON_CODE_INVALID]);
        $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
    }

    #[
        DataFixture(
            SalesRuleFixture::class,
            [
                'coupon_code' => 'coupon%uniqid%',
                'discount_amount' => 10,
                'stop_rules_processing' => false
            ],
            'rule1'
        ),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(QuoteMaskFixture::class, ['cart_id' => '$quote.id$'], 'quoteIdMask')
    ]
    public function testNoProductInCart()
    {
        $this->expectException(ResponseContainsErrorsException::class);
        $this->expectExceptionMessage('Cart does not contain products.');

        $coupon1 = DataFixtureStorageManager::getStorage()->get('rule1')->getData('coupon_code');
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $query = $this->getQuery($maskedQuoteId, [$coupon1]);
        $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
    }

    /**
     * @param string $maskedQuoteId
     * @param array $couponCodes
     * @param string $type
     * @return string
     */
    private function getQuery(string $maskedQuoteId = '', array $couponCodes = [], string $type = ''): string
    {
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
