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
use Magento\Multicoupon\Api\Quote\AddCouponsInterface;
use Magento\Multicoupon\Api\Quote\GetCouponsInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductFixture;
use Magento\Quote\Test\Fixture\CustomerCart as CartFixture;
use Magento\SalesRule\Model\Rule as SalesRule;
use Magento\SalesRule\Test\Fixture\Rule as SalesRuleFixture;
use Magento\SalesRule\Test\Fixture\RuleCoupon as RuleCouponFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQl\ResponseContainsErrorsException;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Coupon management service tests
 */
class CouponManagementTest extends WebapiAbstract
{
    private const SERVICE_VERSION = 'V2';
    private const SERVICE_NAME = 'multiCouponManagementV2';
    private const RESOURCE_PATH = '/V2/carts/';
    private const COUPON_CODE_A = 'COUPON-MULTI-A';
    private const COUPON_CODE_B = 'COUPON-MULTI-B';

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var AddCouponsInterface
     */
    private $addCoupons;

    /**
     * @var GetCouponsInterface
     */
    private $getCoupons;

    protected function setUp(): void
    {
        $this->_markTestAsRestOnly();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->addCoupons = Bootstrap::getObjectManager()->get(AddCouponsInterface::class);
        $this->getCoupons = Bootstrap::getObjectManager()->get(GetCouponsInterface::class);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2'),
        DataFixture(
            SalesRuleFixture::class,
            [
                'coupon_code' => 'coupon%uniqid%',
                'discount_amount' => 10,
                'stop_rules_processing' => false
            ],
            'rule'
        ),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CartFixture::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductFixture::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1])
    ]
    public function testApplyCoupon()
    {
        $coupon = DataFixtureStorageManager::getStorage()->get('rule')->getData('coupon_code');
        $token = $this->getCustomerToken();
        $requestData = ['couponCodes' => [$coupon]];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . 'mine/coupons',
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => $token,
            ],
        ];
        $this->_webApiCall($serviceInfo, $requestData);

        // Test case for get coupons
        $serviceInfoGet = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . 'mine/coupons',
                'httpMethod' => Request::HTTP_METHOD_GET,
                'token' => $token,
            ],
        ];
        $this->assertEquals([$coupon], $this->_webApiCall($serviceInfoGet, []));
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CartFixture::class, ['customer_id' => '$customer.id$'], as: 'quote')
    ]
    public function testGetCouponInEmptyCart()
    {
        $token = $this->getCustomerToken();
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . 'mine/coupons',
                'httpMethod' => Request::HTTP_METHOD_GET,
                'token' => $token,
            ],
        ];
        $this->assertEmpty($this->_webApiCall($serviceInfo, []));
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2'),
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
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CartFixture::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductFixture::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1])
    ]
    public function testAppendCoupon()
    {
        $coupon1 = DataFixtureStorageManager::getStorage()->get('rule1')->getData('coupon_code');
        $coupon2 = DataFixtureStorageManager::getStorage()->get('rule2')->getData('coupon_code');
        $cart = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage()->get('quote');
        $this->addCoupons->execute((string)$cart->getId(), [$coupon2]);

        $token = $this->getCustomerToken();
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . 'mine/coupons',
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => $token,
            ],
        ];
        $requestData = ['couponCodes' => [$coupon1]];
        $this->_webApiCall($serviceInfo, $requestData);
        $this->assertEquals(
            [
                0 => $coupon1,
                1 => $coupon2
            ],
            $this->getCoupons->execute((string)$cart->getId())
        );
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2'),
        DataFixture(
            SalesRuleFixture::class,
            [
                'coupon_code' => 'coupon%uniqid%',
                'discount_amount' => 10,
                'stop_rules_processing' => true
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
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CartFixture::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductFixture::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1])
    ]
    public function testAppendCouponStopFurtherRules()
    {
        $coupon1 = DataFixtureStorageManager::getStorage()->get('rule1')->getData('coupon_code');
        $coupon2 = DataFixtureStorageManager::getStorage()->get('rule2')->getData('coupon_code');
        $this->expectExceptionMessage(
            'The following coupon codes could not be applied: \"' . $coupon2 . '\".'
        );

        $cart = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage()->get('quote');
        $this->addCoupons->execute((string)$cart->getId(), [$coupon2]);

        $token = $this->getCustomerToken();
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . 'mine/coupons',
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => $token,
            ],
        ];
        $this->_webApiCall($serviceInfo, ['couponCodes' => [$coupon1]]);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2'),
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
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CartFixture::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductFixture::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1])
    ]
    public function testAppendWithCartId()
    {
        $coupon1 = DataFixtureStorageManager::getStorage()->get('rule1')->getData('coupon_code');
        $coupon2 = DataFixtureStorageManager::getStorage()->get('rule2')->getData('coupon_code');
        /** @var \Magento\Quote\Model\Quote  $quote */
        $cart = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage()->get('quote');
        $this->addCoupons->execute((string)$cart->getId(), [$coupon2]);
        $cartId = $cart->getId();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . $cartId . '/coupons',
                'httpMethod' => Request::HTTP_METHOD_POST
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Append',
            ],
        ];
        $requestData = ['cartId' => $cartId, 'couponCodes' => [$coupon1]];
        $this->_webApiCall($serviceInfo, $requestData);
        $this->assertEquals(
            [
                0 => $coupon1,
                1 => $coupon2
            ],
            $this->getCoupons->execute((string)$cart->getId())
        );
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CartFixture::class, ['customer_id' => '$customer.id$'], as: 'quote')
    ]
    public function testExceededCouponCount()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'Maximum allowed number of applied coupons was exceeded.'
        );

        /** @var \Magento\Quote\Model\Quote  $quote */
        $cart = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage()->get('quote');
        $this->addCoupons->execute((string)$cart->getId(), [self::COUPON_CODE_B, self::COUPON_CODE_A]);
        $cartId = $cart->getId();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . $cartId . '/coupons',
                'httpMethod' => Request::HTTP_METHOD_POST
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Append',
            ],
        ];
        $requestData = ['cartId' => $cartId, 'couponCodes' => ['COUPON_CODE_C']];
        $this->_webApiCall($serviceInfo, $requestData);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CartFixture::class, ['customer_id' => '$customer.id$'], as: 'quote')
    ]
    public function testCartEmpty()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cart does not contain products.');

        $token = $this->getCustomerToken();
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . 'mine/coupons',
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => $token,
            ],
        ];
        $requestData = ['couponCodes' => ['RANDOM-COUPON']];
        $this->_webApiCall($serviceInfo, $requestData);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2'),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CartFixture::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductFixture::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1])
    ]
    public function testInvalidCoupon()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The following coupon codes could not be applied: \"abcdef\".');

        $token = $this->getCustomerToken();
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . 'mine/coupons',
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => $token,
            ],
        ];
        $requestData = ['couponCodes' => ['abcdef']];
        $this->_webApiCall($serviceInfo, $requestData);
    }

    private function getCustomerToken()
    {
        // get customer ID token
        /** @var \Magento\Integration\Api\CustomerTokenServiceInterface $customerTokenService */
        $customerTokenService = $this->objectManager->create(
            \Magento\Integration\Api\CustomerTokenServiceInterface::class
        );
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        return $customerTokenService->createCustomerAccessToken($customer->getEmail(), 'password');
    }
}
