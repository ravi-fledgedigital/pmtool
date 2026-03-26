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

use Magento\Catalog\Test\Fixture\Product;
use Magento\Checkout\Test\Fixture\SetBillingAddress;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress;
use Magento\Customer\Test\Fixture\Customer;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\Quote\Test\Fixture\QuoteIdMask as QuoteMaskFixture;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\SalesRule\Test\Fixture\Rule;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\MessageQueue\EnvironmentPreconditionException;
use Magento\TestFramework\MessageQueue\PreconditionFailedException;
use Magento\TestFramework\MessageQueue\PublisherConsumerController;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Testing coupon usage increments and validation
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CouponUsageTest extends GraphQlAbstract
{
    private const COUPON_ONE = 'coupon-usage-test-1';
    private const COUPON_TWO = 'coupon-usage-test-2';

    /**
     * @var PublisherConsumerController
     */
    private $publisherConsumerController;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $params = array_merge_recursive(
            Bootstrap::getInstance()->getAppInitParams(),
            ['MAGE_DIRS' => ['cache' => ['path' => TESTS_TEMP_DIR . '/cache']]]
        );

        $this->publisherConsumerController = Bootstrap::getObjectManager()->create(
            PublisherConsumerController::class,
            [
                'consumers'     => ['sales.rule.update.coupon.usage'],
                'logFilePath'   => TESTS_TEMP_DIR . "/MessageQueueTestLog.txt",
                'appInitParams' => $params,
            ]
        );

        try {
            $this->publisherConsumerController->initialize();
        } catch (EnvironmentPreconditionException $e) {
            $this->markTestSkipped($e->getMessage());
        } catch (PreconditionFailedException $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->publisherConsumerController->stopConsumers();
        parent::tearDown();
    }

    #[
        DataFixture(
            Rule::class,
            [
                'coupon_code' => self::COUPON_ONE,
                'discount_amount' => 10,
                'uses_per_coupon' => 1,
                'stop_rules_processing' => false
            ]
        ),
        DataFixture(
            Rule::class,
            [
                'coupon_code' => self::COUPON_TWO,
                'discount_amount' => 10,
                'uses_per_coupon' => 2,
                'stop_rules_processing' => false
            ]
        ),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(Product::class, as: 'product'),
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2'),
    ]
    public function testIncrement()
    {
        $orderNumber1 = $this->placeOrderUsingCoupons([self::COUPON_ONE, self::COUPON_TWO]);
        $this->assertEquals(true, (bool) $this->getOrderByIncrementId($orderNumber1)->getAppliedRuleIds());

        $coupons = $this->getCoupons([self::COUPON_ONE, self::COUPON_TWO]);
        foreach ($coupons as $coupon) {
            self::assertEquals(
                1,
                $coupon->getTimesUsed(),
                'Coupon times used was not incremented for ' . $coupon->getCode()
            );
        }

        $orderNumber2 = $this->placeOrderUsingCoupons([self::COUPON_TWO]);
        $this->assertNotEquals($orderNumber1, $orderNumber2);
        $this->assertEquals(true, (bool) $this->getOrderByIncrementId($orderNumber2)->getAppliedRuleIds());

        $coupons = $this->getCoupons([self::COUPON_TWO]);
        foreach ($coupons as $coupon) {
            self::assertEquals(
                2,
                $coupon->getTimesUsed(),
                'Coupon times used was not incremented for ' . $coupon->getCode()
            );
        }
    }

    /**
     * Retrieve order by increment id
     *
     * @param string $orderNumber
     * @return OrderInterface
     * @throws LocalizedException
     */
    private function getOrderByIncrementId(string $orderNumber): OrderInterface
    {
        $orderRepository = Bootstrap::getObjectManager()->get(OrderRepositoryInterface::class);
        $orders = $orderRepository->getList(
            Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class)
                ->addFilter('increment_id', $orderNumber)
                ->create()
        )->getItems();
        return reset($orders);
    }

    #[
        DataFixture(
            Rule::class,
            [
                'coupon_code' => self::COUPON_ONE,
                'discount_amount' => 10,
                'uses_per_coupon' => 1,
                'stop_rules_processing' => false
            ]
        ),
        DataFixture(
            Rule::class,
            [
                'coupon_code' => self::COUPON_TWO,
                'discount_amount' => 10,
                'uses_per_coupon' => 2,
                'stop_rules_processing' => false
            ]
        ),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(Product::class, as: 'product'),
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2'),
    ]
    public function testDecrementForCancelledOrder()
    {
        $orderNumber = $this->placeOrderUsingCoupons([self::COUPON_ONE, self::COUPON_TWO]);
        $this->assertEquals(true, (bool) $this->getOrderByIncrementId($orderNumber)->getAppliedRuleIds());

        $coupons = $this->getCoupons([self::COUPON_ONE, self::COUPON_TWO]);
        foreach ($coupons as $coupon) {
            self::assertEquals(
                1,
                $coupon->getTimesUsed(),
                'Coupon times used was not incremented for ' . $coupon->getCode()
            );
        }
        $order = $this->getOrderByIncrementId($orderNumber);
        $this->assertEquals(true, (bool) $order->getAppliedRuleIds());

        /** @var OrderManagementInterface $orderManagement */
        $orderManagement = Bootstrap::getObjectManager()->get(OrderManagementInterface::class);
        $orderManagement->cancel($order->getEntityId());

        $coupons = $this->getCoupons([self::COUPON_ONE, self::COUPON_TWO]);
        foreach ($coupons as $coupon) {
            self::assertEquals(
                0,
                $coupon->getTimesUsed(),
                'Coupon times used was not decremented for ' . $coupon->getCode()
            );
        }

        $this->placeOrderUsingCoupons([self::COUPON_ONE, self::COUPON_TWO]);

        $coupons = $this->getCoupons([self::COUPON_ONE, self::COUPON_TWO]);
        foreach ($coupons as $coupon) {
            self::assertEquals(
                1,
                $coupon->getTimesUsed(),
                'Coupon times used was not incremented for ' . $coupon->getCode()
            );
        }
    }

    #[
        DataFixture(
            Rule::class,
            [
                'coupon_code' => self::COUPON_ONE,
                'discount_amount' => 10,
                'uses_per_coupon' => 2,
                'stop_rules_processing' => false
            ]
        ),
        DataFixture(
            Rule::class,
            [
                'coupon_code' => self::COUPON_TWO,
                'discount_amount' => 10,
                'uses_per_coupon' => 1,
                'stop_rules_processing' => false
            ]
        ),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(Customer::class, as: 'customer2'),
        DataFixture(Product::class, as: 'product'),
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2'),
    ]
    public function testReachGeneralUsageLimit()
    {
        $this->placeOrderUsingCoupons([self::COUPON_ONE, self::COUPON_TWO]);

        $this->expectExceptionMessage('The following coupon codes could not be applied: "' . self::COUPON_TWO . '"');

        $this->placeOrderUsingCoupons([self::COUPON_ONE, self::COUPON_TWO], 'customer2');
    }

    #[
        DataFixture(
            Rule::class,
            [
                'coupon_code' => self::COUPON_ONE,
                'discount_amount' => 10,
                'uses_per_customer' => 2,
                'stop_rules_processing' => false
            ]
        ),
        DataFixture(
            Rule::class,
            [
                'coupon_code' => self::COUPON_TWO,
                'discount_amount' => 10,
                'uses_per_customer' => 1,
                'stop_rules_processing' => false
            ]
        ),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(Product::class, as: 'product'),
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2'),
    ]
    public function testReachCustomerUsageLimit()
    {
        $this->placeOrderUsingCoupons([self::COUPON_ONE, self::COUPON_TWO]);

        $this->expectExceptionMessage('The following coupon codes could not be applied: "' . self::COUPON_TWO . '"');

        $this->placeOrderUsingCoupons([self::COUPON_ONE, self::COUPON_TWO]);
    }

    /**
     * Retrieve coupons by codes
     *
     * @param string[] $codes
     * @return CouponInterface[]
     * @throws LocalizedException
     */
    private function getCoupons(array $codes): array
    {
        return Bootstrap::getObjectManager()->get(CouponRepositoryInterface::class)->getList(
            Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class)
                ->addFilter('code', $codes, 'in')
                ->create()
        )->getItems();
    }

    /**
     * Prepare quote and place order for customer using coupons
     *
     * @param array $codes
     * @param string $customer
     * @return string
     * @throws LocalizedException
     */
    private function placeOrderUsingCoupons(array $codes, string $customer = 'customer'): string
    {
        $maskedQuoteId = $this->prepareQuote($customer);
        $this->assertEquals(
            [
                'applyCouponsToCart' => [
                    'cart' => [
                        'applied_coupons' => array_map(
                            function (string $code) {
                                return ['code' => $code];
                            },
                            $codes
                        )
                    ]
                ]
            ],
            $this->graphQlMutation(
                $this->getApplyQuery($maskedQuoteId, $codes),
                [],
                '',
                $this->getCustomerHeader($customer)
            )
        );
        $placeOrderResult = $this->graphQlMutation(
            $this->getPlaceOrderQuery($maskedQuoteId),
            [],
            '',
            $this->getCustomerHeader($customer)
        );
        $this->assertNotEmpty($placeOrderResult['placeOrder']['orderV2']['applied_coupons']);
        $this->assertEquals(
            array_map(
                function (string $code) {
                    return ['code' => $code];
                },
                $codes
            ),
            $placeOrderResult['placeOrder']['orderV2']['applied_coupons']
        );
        $this->assertNotEmpty($placeOrderResult['placeOrder']['orderV2']['number']);
        return $placeOrderResult['placeOrder']['orderV2']['number'];
    }

    private function prepareQuote(string $customer = 'customer'): string
    {
        $productId = DataFixtureStorageManager::getStorage()->get('product')->getId();
        $customerId = DataFixtureStorageManager::getStorage()->get($customer)->getId();
        $objectManager = Bootstrap::getObjectManager();
        $quote = $objectManager->create(CustomerCart::class)->apply(['customer_id' => $customerId]);
        $objectManager->create(AddProductToCart::class)->apply(
            ['cart_id' => $quote->getId(), 'product_id' => $productId, 'qty' => 1]
        );
        $objectManager->create(SetBillingAddress::class)->apply(['cart_id' => $quote->getId()]);
        $objectManager->create(SetShippingAddress::class)->apply(['cart_id' => $quote->getId()]);
        $objectManager->create(SetDeliveryMethodFixture::class)->apply(['cart_id' => $quote->getId()]);
        $objectManager->create(SetPaymentMethodFixture::class)->apply(['cart_id' => $quote->getId()]);
        return $objectManager->create(QuoteMaskFixture::class)->apply(['cart_id' => $quote->getId()])->getMaskedId();
    }

    /**
     * @param string $maskedQuoteId
     * @param array $couponCodes
     * @return string
     */
    private function getApplyQuery(string $maskedQuoteId, array $couponCodes): string
    {
        $codes = 'coupon_codes: ["' . implode('","', $couponCodes) . '"]';
        return <<<QUERY
mutation {
  applyCouponsToCart(input: {cart_id: "$maskedQuoteId", $codes}) {
    cart {
      applied_coupons {
        code
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
    private function getPlaceOrderQuery(string $maskedQuoteId): string
    {
        return <<<QUERY
mutation {
  placeOrder(input: {cart_id: "{$maskedQuoteId}"}) {
    orderV2 {
      number
      applied_coupons {
        code
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
            ->execute(DataFixtureStorageManager::getStorage()->get($customerFixtureName)->getEmail());
    }
}
