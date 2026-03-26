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

namespace Magento\Multicoupon\Api;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Customer\Test\Fixture\Customer as CustomerFixture;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductFixture;
use Magento\Quote\Test\Fixture\CustomerCart as CartFixture;
use Magento\SalesRule\Test\Fixture\Rule as SalesRuleFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Coupon management service test
 */
class CouponManagementReplaceTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V2/carts/';
    private const COUPON_CODE_A = 'COUPON-MULTI-A';
    private const COUPON_CODE_B = 'COUPON-MULTI-B';

    protected function setUp(): void
    {
        $this->_markTestAsRestOnly();
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2'),
        DataFixture(
            SalesRuleFixture::class,
            [
                'coupon_code' => self::COUPON_CODE_A,
                'discount_amount' => 10,
                'stop_rules_processing' => false
            ]
        ),
        DataFixture(
            SalesRuleFixture::class,
            [
                'coupon_code' => self::COUPON_CODE_B,
                'discount_amount' => 10,
                'stop_rules_processing' => false
            ]
        ),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CartFixture::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductFixture::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1])
    ]
    public function testReplaceMine()
    {
        $token = $this->getCustomerToken();
        $get = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . 'mine/coupons',
                'httpMethod' => Request::HTTP_METHOD_GET,
                'token' => $token,
            ],
        ];
        $replace = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . 'mine/coupons',
                'httpMethod' => Request::HTTP_METHOD_PUT,
                'token' => $token,
            ],
        ];

        $this->_webApiCall($replace, ['couponCodes' => [self::COUPON_CODE_B]]);
        $this->assertEquals([self::COUPON_CODE_B], $this->_webApiCall($get, []));

        $this->_webApiCall($replace, ['couponCodes' => [self::COUPON_CODE_A]]);
        $this->assertEquals([self::COUPON_CODE_A], $this->_webApiCall($get, []));

        $this->_webApiCall($replace, ['couponCodes' => []]);
        $this->assertEquals([], $this->_webApiCall($get, []));

        $this->_webApiCall($replace, ['couponCodes' => [self::COUPON_CODE_B, self::COUPON_CODE_A]]);
        $this->assertEquals([self::COUPON_CODE_A, self::COUPON_CODE_B], $this->_webApiCall($get, []));
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2'),
        DataFixture(
            SalesRuleFixture::class,
            [
                'coupon_code' => self::COUPON_CODE_A,
                'discount_amount' => 10,
                'stop_rules_processing' => false
            ]
        ),
        DataFixture(
            SalesRuleFixture::class,
            [
                'coupon_code' => self::COUPON_CODE_B,
                'discount_amount' => 10,
                'stop_rules_processing' => false
            ]
        ),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CartFixture::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductFixture::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1])
    ]
    public function testReplaceForCartId()
    {
        $cartId = Bootstrap::getObjectManager()
            ->get(DataFixtureStorageManager::class)
            ->getStorage()
            ->get('quote')
            ->getId();
        $get = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . $cartId .'/coupons',
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
        ];
        $replace = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . $cartId . '/coupons',
                'httpMethod' => Request::HTTP_METHOD_PUT,
            ],
        ];

        $this->_webApiCall($replace, ['cartId' => $cartId, 'couponCodes' => [self::COUPON_CODE_B]]);
        $this->assertEquals([self::COUPON_CODE_B], $this->_webApiCall($get, []));

        $this->_webApiCall($replace, ['cartId' => $cartId, 'couponCodes' => [self::COUPON_CODE_A]]);
        $this->assertEquals([self::COUPON_CODE_A], $this->_webApiCall($get, []));

        $this->_webApiCall($replace, ['cartId' => $cartId, 'couponCodes' => []]);
        $this->assertEquals([], $this->_webApiCall($get, []));

        $this->_webApiCall(
            $replace,
            [
                'cartId' => $cartId,
                'couponCodes' => [self::COUPON_CODE_B, self::COUPON_CODE_A]
            ]
        );
        $this->assertEquals([self::COUPON_CODE_A, self::COUPON_CODE_B], $this->_webApiCall($get, []));
    }

    private function getCustomerToken()
    {
        return Bootstrap::getObjectManager()->create(CustomerTokenServiceInterface::class)->createCustomerAccessToken(
            DataFixtureStorageManager::getStorage()->get('customer')->getEmail(),
            'password'
        );
    }
}
