<?php
/**
 * Copyright 2024 Adobe
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
use Magento\Checkout\Test\Fixture\SetBillingAddress;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress;
use Magento\Multicoupon\Test\Fixture\ApplyCoupon;
use Magento\Quote\Test\Fixture\GuestCart;
use Magento\Quote\Test\Fixture\QuoteIdMask as QuoteMaskFixture;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\SalesRule\Test\Fixture\Rule;
use Magento\TestFramework\Annotation\DataFixtureSetup;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class RuleLabelsTest extends GraphQlAbstract
{
    #[
        DataFixture(
            Rule::class,
            [
                'coupon_code' => 'coupon%uniqid%',
                'discount_amount' => 10,
                'simple_action' => \Magento\SalesRule\Model\Rule::BY_PERCENT_ACTION,
                'stop_rules_processing' => false,
                'store_labels' => ['Rule1 Store Label']
            ],
            'rule1'
        ),
        DataFixture(
            Rule::class,
            [
                'coupon_code' => 'coupon%uniqid%',
                'discount_amount' => 10,
                'simple_action' => \Magento\SalesRule\Model\Rule::BY_FIXED_ACTION,
                'stop_rules_processing' => false,
                'description' => 'Rule2 Description'
            ],
            'rule2'
        ),
        DataFixture(
            Rule::class,
            [
                'coupon_code' => 'coupon%uniqid%',
                'discount_amount' => 10,
                'simple_action' => \Magento\SalesRule\Model\Rule::BY_PERCENT_ACTION,
                'stop_rules_processing' => false,
                'apply_to_shipping' => 1
            ],
            'rule3'
        ),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(GuestCart::class, as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(QuoteMaskFixture::class, ['cart_id' => '$quote.id$'], 'quoteIdMask'),
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '3')
    ]
    public function testApplyCoupon()
    {
        Bootstrap::getObjectManager()->get(DataFixtureSetup::class)->apply(
            [
                'factory' => ApplyCoupon::class,
                'data' => [
                    'cart_id' => '$quote.id$',
                    'coupon_codes' => [
                        '$rule1.coupon_code$',
                        '$rule2.coupon_code$',
                        '$rule3.coupon_code$'
                    ]
                ]
            ]
        );
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $response = $this->graphQlQuery($this->getQuery($maskedQuoteId));
        self::assertEquals(
            [
                'cart' => [
                    'prices' => [
                        'discounts' => [
                            [
                                'label' => 'Rule1 Store Label',
                                'applied_to' => 'ITEM',
                                'amount' => [
                                    'value' => 10
                                ]
                            ],
                            [
                                'label' => 'Rule2 Description',
                                'applied_to' => 'ITEM',
                                'amount' => [
                                    'value' => 10
                                ]
                            ],
                            [
                                'label' => DataFixtureStorageManager::getStorage()->get('rule3')->getCouponCode(),
                                'applied_to' => 'ITEM',
                                'amount' => [
                                    'value' => 8
                                ]
                            ],
                            [
                                'label' => DataFixtureStorageManager::getStorage()->get('rule3')->getCouponCode(),
                                'applied_to' => 'SHIPPING',
                                'amount' => [
                                    'value' => 0.5
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    /**
     * @param string $maskedQuoteId
     * @return string
     */
    private function getQuery(string $maskedQuoteId): string
    {
        return <<<QUERY
{
  cart(cart_id: "{$maskedQuoteId}") {
    prices {
      discounts {
        label
        applied_to
        amount {
          value
        }
      }
    }
  }
}
QUERY;
    }
}
