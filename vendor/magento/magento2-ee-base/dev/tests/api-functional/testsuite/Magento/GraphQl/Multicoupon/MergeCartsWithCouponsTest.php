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
use Magento\Quote\Test\Fixture\GuestCart;
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
 * Test for merging guest and customer quotes with applied coupons
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
    DataFixture(
        SalesRuleFixture::class,
        [
            'coupon_code' => 'coupon%uniqid%',
            'discount_amount' => 10,
            'stop_rules_processing' => false
        ],
        'rule4'
    ),
    DataFixture(
        SalesRuleFixture::class,
        [
            'coupon_code' => 'coupon%uniqid%',
            'discount_amount' => 10,
            'stop_rules_processing' => false
        ],
        'rule5'
    ),
    DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product1'),
    DataFixture(ProductFixture::class, ['price' => 200.00], as: 'product2'),
    DataFixture(Customer::class, as: 'customer'),
    DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
    DataFixture(GuestCart::class, [], as: 'guest_quote'),
    DataFixture(AddProductToCart::class, [
        'cart_id' => '$quote.id$',
        'product_id' => '$product1.id$','qty' => 1
    ]),
    DataFixture(AddProductToCart::class, [
        'cart_id' => '$guest_quote.id$',
        'product_id' => '$product2.id$',
        'qty' => 2
    ]),
    DataFixture(QuoteMaskFixture::class, ['cart_id' => '$quote.id$'], 'quoteIdMask'),
    DataFixture(QuoteMaskFixture::class, ['cart_id' => '$guest_quote.id$'], 'guestQuoteIdMask')
]
class MergeCartsWithCouponsTest extends GraphQlAbstract
{
    /**
     * @var AddCouponsInterface
     */
    private $addCoupons;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerHeaders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addCoupons = Bootstrap::getObjectManager()->get(AddCouponsInterface::class);
        $this->getCustomerHeaders = Bootstrap::getObjectManager()->get(GetCustomerAuthenticationHeader::class);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '3')
    ]
    public function testMergeQuotesWithTheSameCouponsApplied()
    {
        $coupon1 = DataFixtureStorageManager::getStorage()->get('rule1')->getData('coupon_code');

        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $guestMaskedQuoteId = DataFixtureStorageManager::getStorage()->get('guestQuoteIdMask')->getMaskedId();

        $query = $this->getQuery($maskedQuoteId, [$coupon1]);
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
        self::assertArrayHasKey('applyCouponsToCart', $response);

        $guestQuoteQuery = $this->getQuery($guestMaskedQuoteId, [$coupon1]);
        $response = $this->graphQlMutation($guestQuoteQuery);
        self::assertArrayHasKey('applyCouponsToCart', $response);

        $query = $this->getCartMergeMutation($guestMaskedQuoteId, $maskedQuoteId);
        $mergeResponse = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
        self::assertArrayHasKey('mergeCarts', $mergeResponse);
        $mergeCarts = $mergeResponse['mergeCarts'];
        self::assertArrayHasKey('items', $mergeCarts);

        self::assertCount(2, $mergeCarts['items']);
        self::assertCount(1, $mergeCarts['applied_coupons']);
        self::assertEquals($coupon1, $mergeCarts['applied_coupons'][0]['code']);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '3')
    ]
    public function testMergeQuotesWithTwoDifferentCouponsApplied()
    {
        $coupon1 = DataFixtureStorageManager::getStorage()->get('rule1')->getData('coupon_code');
        $coupon2 = DataFixtureStorageManager::getStorage()->get('rule2')->getData('coupon_code');

        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $guestMaskedQuoteId = DataFixtureStorageManager::getStorage()->get('guestQuoteIdMask')->getMaskedId();

        $query = $this->getQuery($maskedQuoteId, [$coupon1]);
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
        self::assertArrayHasKey('applyCouponsToCart', $response);

        $guestQuoteQuery = $this->getQuery($guestMaskedQuoteId, [$coupon2]);
        $response = $this->graphQlMutation($guestQuoteQuery);
        self::assertArrayHasKey('applyCouponsToCart', $response);

        $query = $this->getCartMergeMutation($guestMaskedQuoteId, $maskedQuoteId);
        $mergeResponse = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
        self::assertArrayHasKey('mergeCarts', $mergeResponse);
        $mergeCarts = $mergeResponse['mergeCarts'];
        self::assertArrayHasKey('items', $mergeCarts);

        self::assertCount(2, $mergeCarts['items']);
        self::assertCount(2, $mergeCarts['applied_coupons']);
        self::assertEquals($coupon1, $mergeCarts['applied_coupons'][1]['code']);
        self::assertEquals($coupon2, $mergeCarts['applied_coupons'][0]['code']);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '3')
    ]
    public function testMergeQuotesWithTheSameCouponsAndOneDifferentApplied()
    {
        $coupon1 = DataFixtureStorageManager::getStorage()->get('rule1')->getData('coupon_code');
        $coupon2 = DataFixtureStorageManager::getStorage()->get('rule2')->getData('coupon_code');

        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $guestMaskedQuoteId = DataFixtureStorageManager::getStorage()->get('guestQuoteIdMask')->getMaskedId();

        $query = $this->getQuery($maskedQuoteId, [$coupon1]);
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
        self::assertArrayHasKey('applyCouponsToCart', $response);

        $guestQuoteQuery = $this->getQuery($guestMaskedQuoteId, [$coupon1, $coupon2]);
        $response = $this->graphQlMutation($guestQuoteQuery);
        self::assertArrayHasKey('applyCouponsToCart', $response);

        $query = $this->getCartMergeMutation($guestMaskedQuoteId, $maskedQuoteId);
        $mergeResponse = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
        self::assertArrayHasKey('mergeCarts', $mergeResponse);
        $mergeCarts = $mergeResponse['mergeCarts'];
        self::assertArrayHasKey('items', $mergeCarts);

        self::assertCount(2, $mergeCarts['items']);
        self::assertCount(2, $mergeCarts['applied_coupons']);
        self::assertEquals($coupon1, $mergeCarts['applied_coupons'][0]['code']);
        self::assertEquals($coupon2, $mergeCarts['applied_coupons'][1]['code']);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '3')
    ]
    public function testMergeQuotesWithDifferentCouponsApplied()
    {
        $coupon1 = DataFixtureStorageManager::getStorage()->get('rule1')->getData('coupon_code');
        $coupon2 = DataFixtureStorageManager::getStorage()->get('rule2')->getData('coupon_code');
        $coupon3 = DataFixtureStorageManager::getStorage()->get('rule3')->getData('coupon_code');
        
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $guestMaskedQuoteId = DataFixtureStorageManager::getStorage()->get('guestQuoteIdMask')->getMaskedId();

        $query = $this->getQuery($maskedQuoteId, [$coupon1, $coupon3]);
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
        self::assertArrayHasKey('applyCouponsToCart', $response);
        
        $guestQuoteQuery = $this->getQuery($guestMaskedQuoteId, [$coupon2]);
        $response = $this->graphQlMutation($guestQuoteQuery);
        self::assertArrayHasKey('applyCouponsToCart', $response);

        $query = $this->getCartMergeMutation($guestMaskedQuoteId, $maskedQuoteId);
        $mergeResponse = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
        self::assertArrayHasKey('mergeCarts', $mergeResponse);
        $mergeCarts = $mergeResponse['mergeCarts'];
        self::assertArrayHasKey('items', $mergeCarts);

        self::assertCount(2, $mergeCarts['items']);
        self::assertCount(3, $mergeCarts['applied_coupons']);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '5')
    ]
    public function testMergeQuotesMultipleDifferentCouponsAppliedToBothCarts()
    {
        $coupon1 = DataFixtureStorageManager::getStorage()->get('rule1')->getData('coupon_code');
        $coupon2 = DataFixtureStorageManager::getStorage()->get('rule2')->getData('coupon_code');
        $coupon3 = DataFixtureStorageManager::getStorage()->get('rule3')->getData('coupon_code');
        $coupon4 = DataFixtureStorageManager::getStorage()->get('rule4')->getData('coupon_code');
        $coupon5 = DataFixtureStorageManager::getStorage()->get('rule5')->getData('coupon_code');
        
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $guestMaskedQuoteId = DataFixtureStorageManager::getStorage()->get('guestQuoteIdMask')->getMaskedId();

        $query = $this->getQuery($maskedQuoteId, [$coupon1, $coupon3, $coupon4]);
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
        self::assertArrayHasKey('applyCouponsToCart', $response);

        $guestQuoteQuery = $this->getQuery($guestMaskedQuoteId, [$coupon2, $coupon5]);
        $response = $this->graphQlMutation($guestQuoteQuery);
        self::assertArrayHasKey('applyCouponsToCart', $response);

        $query = $this->getCartMergeMutation($guestMaskedQuoteId, $maskedQuoteId);
        $mergeResponse = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
        self::assertArrayHasKey('mergeCarts', $mergeResponse);
        $mergeCarts = $mergeResponse['mergeCarts'];
        self::assertArrayHasKey('items', $mergeCarts);

        self::assertCount(2, $mergeCarts['items']);
        self::assertCount(5, $mergeCarts['applied_coupons']);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '3')
    ]
    public function testMergeQuotesMultipleCouponsAppliedOnlyToCustomerCarts()
    {
        $coupon1 = DataFixtureStorageManager::getStorage()->get('rule1')->getData('coupon_code');
        $coupon2 = DataFixtureStorageManager::getStorage()->get('rule2')->getData('coupon_code');
        $coupon3 = DataFixtureStorageManager::getStorage()->get('rule3')->getData('coupon_code');
        
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $guestMaskedQuoteId = DataFixtureStorageManager::getStorage()->get('guestQuoteIdMask')->getMaskedId();

        $query = $this->getQuery($maskedQuoteId, [$coupon1, $coupon2, $coupon3]);
        $response = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
        self::assertArrayHasKey('applyCouponsToCart', $response);

        $query = $this->getCartMergeMutation($guestMaskedQuoteId, $maskedQuoteId);
        $mergeResponse = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
        self::assertArrayHasKey('mergeCarts', $mergeResponse);
        $mergeCarts = $mergeResponse['mergeCarts'];
        self::assertArrayHasKey('items', $mergeCarts);

        self::assertCount(2, $mergeCarts['items']);
        self::assertCount(3, $mergeCarts['applied_coupons']);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '3')
    ]
    public function testMergeQuotesMultipleCouponsAppliedOnlyToGuestCarts()
    {
        $coupon1 = DataFixtureStorageManager::getStorage()->get('rule1')->getData('coupon_code');
        $coupon2 = DataFixtureStorageManager::getStorage()->get('rule2')->getData('coupon_code');
        $coupon3 = DataFixtureStorageManager::getStorage()->get('rule3')->getData('coupon_code');
        
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $guestMaskedQuoteId = DataFixtureStorageManager::getStorage()->get('guestQuoteIdMask')->getMaskedId();
        $guestQuoteQuery = $this->getQuery($guestMaskedQuoteId, [$coupon1, $coupon2, $coupon3]);
        $response = $this->graphQlMutation($guestQuoteQuery);
        self::assertArrayHasKey('applyCouponsToCart', $response);

        $query = $this->getCartMergeMutation($guestMaskedQuoteId, $maskedQuoteId);
        $mergeResponse = $this->graphQlMutation($query, [], '', $this->getCustomerHeader());
        self::assertArrayHasKey('mergeCarts', $mergeResponse);
        $mergeCarts = $mergeResponse['mergeCarts'];
        self::assertArrayHasKey('items', $mergeCarts);

        self::assertCount(2, $mergeCarts['items']);
        self::assertCount(3, $mergeCarts['applied_coupons']);
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
     * Create the mergeCart mutation
     *
     * @param string $guestQuoteMaskedId
     * @param string $customerQuoteMaskedId
     * @return string
     */
    private function getCartMergeMutation(string $guestQuoteMaskedId, string $customerQuoteMaskedId): string
    {
        return <<<QUERY
mutation {
    mergeCarts(
        source_cart_id: "{$guestQuoteMaskedId}"
        destination_cart_id: "{$customerQuoteMaskedId}"
    ){
    applied_coupons {
        code
    }
    items {
        quantity
        product {
            sku
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
