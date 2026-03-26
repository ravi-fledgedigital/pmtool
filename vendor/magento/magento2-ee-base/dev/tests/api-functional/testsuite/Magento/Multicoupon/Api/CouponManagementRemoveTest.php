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
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\CustomerCart as CustomerCartFixture;
use Magento\SalesRule\Model\Rule as SalesRule;
use Magento\SalesRule\Test\Fixture\Rule as SalesRuleFixture;
use Magento\SalesRule\Test\Fixture\RuleCoupon as RuleCouponFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;
use Magento\TestFramework\Fixture\Config;
use Magento\Multicoupon\Test\Fixture\ApplyCoupon as ApplyCouponsFixture;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Integration\Api\CustomerTokenServiceInterface;

class CouponManagementRemoveTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V2/carts/';
    private const COUPON_CODE_A = 'COUPON-A';
    private const COUPON_CODE_B = 'COUPON-B';
    private const COUPON_CODES = ['COUPON-A', 'COUPON-B'];

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    protected function setUp(): void
    {
        $this->_markTestAsRestOnly();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->fixtures = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage();
        $this->quoteRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
    }

    #[
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(ProductFixture::class, as: 'p2'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer.id$'], 'cart'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$p1.id$', 'qty' => 1]),
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
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2')
    ]
    public function testDeleteMineAllCoupons()
    {
        $cart = $this->fixtures->get('cart');
        /** @var CartInterface $quote */
        $quote = $this->quoteRepository->get($cart->getId());
        $quote->getExtensionAttributes()->setCouponCodes(self::COUPON_CODES);
        $this->quoteRepository->save($quote->collectTotals());
        $this->assertEquals(self::COUPON_CODES, array_values($quote->getExtensionAttributes()->getCouponCodes()));
        $customer = $this->fixtures->get('customer');

        // get customer ID token
        $customerTokenService = $this->objectManager->create(
            CustomerTokenServiceInterface::class
        );
        $token = $customerTokenService->createCustomerAccessToken($customer->getEmail(), 'password');
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . 'mine/coupons/deleteByCodes',
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => $token,
            ],
        ];
        $this->_webApiCall($serviceInfo, []);
        $quoteRepository = $this->objectManager->create(CartRepositoryInterface::class);
        $quote = $quoteRepository->getActive($cart->getId());
        $this->assertEmpty($quote->getExtensionAttributes()->getCouponCodes());
    }

    #[
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(ProductFixture::class, as: 'p2'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer.id$'], 'cart'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$p1.id$', 'qty' => 1]),
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
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2')
    ]
    public function testDeleteMineCoupon()
    {
        $customer = $this->fixtures->get('customer');
        // get customer ID token
        $customerTokenService = $this->objectManager->get(CustomerTokenServiceInterface::class);
        $token = $customerTokenService->createCustomerAccessToken($customer->getEmail(), 'password');
        $delete = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . 'mine/coupons/deleteByCodes',
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => $token,
            ],
        ];
        $get = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . 'mine/coupons',
                'httpMethod' => Request::HTTP_METHOD_GET,
                'token' => $token,
            ],
        ];
        $append = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . 'mine/coupons',
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => $token,
            ],
        ];

        $this->_webApiCall($append, ['couponCodes' => self::COUPON_CODES]);
        $this->assertEquals(self::COUPON_CODES, $this->_webApiCall($get, []));

        $this->_webApiCall($delete, ['couponCodes' => [self::COUPON_CODE_B]]);
        $this->assertEquals([self::COUPON_CODE_A], $this->_webApiCall($get, []));
    }
}
