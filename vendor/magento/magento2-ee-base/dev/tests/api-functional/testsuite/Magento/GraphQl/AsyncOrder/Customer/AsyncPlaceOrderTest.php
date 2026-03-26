<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\AsyncOrder\Customer;

use Magento\AsyncOrder\Test\Fixture\AsyncMode;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Catalog\Test\Fixture\ProductStock as ProductStockFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Customer\Test\Fixture\Customer;
use Magento\Framework\Registry;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\Indexer\Test\Fixture\Indexer;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\Quote\Test\Fixture\QuoteIdMask;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\MessageQueue\EnvironmentPreconditionException;
use Magento\TestFramework\MessageQueue\PublisherConsumerController;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for asynchronous checkout by registered customer
 */
class AsyncPlaceOrderTest extends GraphQlAbstract
{
    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
        $this->customerTokenService = $objectManager->get(CustomerTokenServiceInterface::class);
        $this->orderCollectionFactory = $objectManager->get(CollectionFactory::class);
        $this->orderRepository = $objectManager->get(OrderRepositoryInterface::class);
        $this->registry = Bootstrap::getObjectManager()->get(Registry::class);
    }

    /**
     * Tests asynchronous checkout with simple product
     *
     * @magentoApiDataFixture Magento/AsyncOrder/_files/async_mode.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoConfigFixture default_store carriers/flatrate/active 1
     * @magentoConfigFixture default_store payment/checkmo/active 1
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_billing_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_flatrate_shipping_method.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_checkmo_payment_method.php
     */
    public function testAsyncPlaceOrder()
    {
        $reservedOrderId = 'test_quote';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute($reservedOrderId);

        $query = $this->placeOrderQuery($maskedQuoteId);
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());

        $this->assertArrayHasKey('placeOrder', $response);
        $this->assertArrayHasKey('order_number', $response['placeOrder']['order']);
        $orderNumber = $response['placeOrder']['order']['order_number'];
        $customerOrderResponse = $this->getCustomerOrderQuery($orderNumber);
        $orderStatus = $customerOrderResponse['customer']['orders']['items'][0]['status'];
        //check the order status before the placeOrder consumer is run
        $this->assertEquals('Received', $orderStatus);
        $this->assertNull($customerOrderResponse['customer']['orders']['items'][0]['shipping_address']);
        $this->assertNull($customerOrderResponse['customer']['orders']['items'][0]['billing_address']);
        $this->assertEmpty($customerOrderResponse['customer']['orders']['items'][0]['shipping_address']);
        // run the consumer
        $this->runPlaceOrderProcessorConsumer();
        // query the  customer order after consumer is run
        $customerOrderResponse = $this->getCustomerOrderQuery($orderNumber);
        $orderStatus = $customerOrderResponse['customer']['orders']['items'][0]['status'];
        $this->assertEquals('Pending', $orderStatus);
        $this->assertBillingAddressFields($customerOrderResponse['customer']['orders']['items'][0]['billing_address']);
        $this->assertShippingAddressFields(
            $customerOrderResponse['customer']['orders']['items'][0]['shipping_address']
        );
    }

    /**
     *  Tests asynchronous checkout with virtual product
     *
     * @magentoApiDataFixture Magento/AsyncOrder/_files/async_mode.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_virtual.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_virtual_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_billing_address.php
     */
    public function testAsyncPlaceOrderWithVirtualProduct()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $methodCode = Checkmo::PAYMENT_METHOD_CHECKMO_CODE;
        $setPaymentMethodOnCartQuery = <<<QUERY
mutation {
  setPaymentMethodOnCart(input: {
      cart_id: "$maskedQuoteId"
      payment_method: {
          code: "$methodCode"
      }
  }) {
    cart {
      selected_payment_method { code }
    }
  }
}
QUERY;
        $setPaymentResponse = $this->graphQlMutation($setPaymentMethodOnCartQuery, [], '', $this->getHeaderMap());
        //verify the payment method is set successfully on the cart
        $this->assertArrayHasKey('selected_payment_method', $setPaymentResponse['setPaymentMethodOnCart']['cart']);
        $this->assertEquals(
            $methodCode,
            $setPaymentResponse['setPaymentMethodOnCart']['cart']['selected_payment_method']['code']
        );
        $placeOrderQuery = $this->placeOrderQuery($maskedQuoteId);
        $response = $this->graphQlMutation($placeOrderQuery, [], '', $this->getHeaderMap());

        $this->assertArrayHasKey('placeOrder', $response);
        $this->assertArrayHasKey('order_number', $response['placeOrder']['order']);
        $orderNumber = $response['placeOrder']['order']['order_number'];
        $customerOrderResponse = $this->getCustomerOrderQuery($orderNumber);
        $orderStatus = $customerOrderResponse['customer']['orders']['items'][0]['status'];
        //check the order status before the placeOrder consumer is run
        $this->assertEquals('Received', $orderStatus);
        // run the consumer
        $this->runPlaceOrderProcessorConsumer();
        // query the  customer order after consumer is run
        $customerOrderAfterConsumer = $this->getCustomerOrderQuery($orderNumber);
        $orderStatus = $customerOrderAfterConsumer['customer']['orders']['items'][0]['status'];
        $this->assertEquals('Pending', $orderStatus);
    }

    /**
     * Tests that a order can be placed as zero subtotal checkout
     *
     * @magentoApiDataFixture Magento/AsyncOrder/_files/async_mode.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/enable_offline_shipping_methods.php
     * @magentoConfigFixture default_store payment/checkmo/active 1
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_billing_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_freeshipping_shipping_method.php
     * @magentoApiDataFixture Magento/SalesRule/_files/cart_rule_100_percent_off_with_coupon.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/apply_coupon_100percent_discount.php
     */
    public function testAsyncPlaceOrderWithZeroSubtotal()
    {
        $reservedOrderId = 'test_quote';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute($reservedOrderId);
        $paymentMethodCode = 'free';
        $this->setPaymentMethod($maskedQuoteId, $paymentMethodCode);
        $placeOrderQuery = $this->placeOrderQuery($maskedQuoteId);
        $response = $this->graphQlMutation($placeOrderQuery, [], '', $this->getHeaderMap());
        $this->assertArrayHasKey('placeOrder', $response);
        $this->assertArrayHasKey('order_number', $response['placeOrder']['order']);
        $orderNumber = $response['placeOrder']['order']['order_number'];
        $customerOrderResponse = $this->getCustomerOrderQuery($orderNumber);
        $orderStatus = $customerOrderResponse['customer']['orders']['items'][0]['status'];
        //check the order status before the "placeOrder" consumer is run
        $this->assertEquals('Received', $orderStatus);
        //Run the consumer
        $this->runPlaceOrderProcessorConsumer();
        $customerOrderAfterConsumer = $this->getCustomerOrderQuery($orderNumber);
        $orderStatus = $customerOrderAfterConsumer['customer']['orders']['items'][0]['status'];
        // the order status changes to "Pending" after the consumer is run and the order is processed
        $this->assertEquals('Pending', $orderStatus);
        $paymentMethodFromResponse = $customerOrderAfterConsumer['customer']['orders']['items'][0]['payment_methods'];
        $this->assertEquals('free', $paymentMethodFromResponse[0]['type']);
        $this->assertEquals(
            'Free Shipping - Free',
            $customerOrderAfterConsumer['customer']['orders']['items'][0]['shipping_method']
        );
        $this->assertEquals(
            0,
            $customerOrderAfterConsumer['customer']['orders']['items'][0]['total']['base_grand_total']['value']
        );
        $this->assertEquals(
            0,
            $customerOrderAfterConsumer['customer']['orders']['items'][0]['total']['grand_total']['value']
        );
        $this->assertEquals(
            20,
            $customerOrderAfterConsumer['customer']['orders']['items'][0]['total']['subtotal']['value']
        );
    }

    #[
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Indexer::class, as: 'indexer'),
        DataFixture(AsyncMode::class),
        DataFixture(Customer::class, ['email' => 'customer@example.com'], as: 'customer'),
        DataFixture(
            CustomerCart::class,
            [
                'customer_id' => '$customer.id$',
                'reserved_order_id' => 'test_quote'
            ],
            'cart'
        ),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$']),
        DataFixture(QuoteIdMask::class, ['cart_id' => '$cart.id$'], 'quoteIdMask'),
    ]
    public function testAsyncPlaceOrderWithNoShippingAddress()
    {
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $query = $this->placeOrderQuery($maskedQuoteId);
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertEquals(1, count($response['placeOrder']['errors']));
        $this->assertEquals('UNABLE_TO_PLACE_ORDER', $response['placeOrder']['errors'][0]['code']);
        $this->assertEquals(
            'Some addresses can\'t be used due to the configurations for specific countries.',
            $response['placeOrder']['errors'][0]['message']
        );
    }

    #[
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Indexer::class, as: 'indexer'),
        DataFixture(AsyncMode::class),
        DataFixture(Customer::class, ['email' => 'customer@example.com'], as: 'customer'),
        DataFixture(
            CustomerCart::class,
            [
                'customer_id' => '$customer.id$',
                'reserved_order_id' => 'test_quote'
            ],
            'cart'
        ),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$']),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(QuoteIdMask::class, ['cart_id' => '$cart.id$'], 'quoteIdMask'),
    ]
    public function testAsyncPlaceOrderWithNoShippingMethod()
    {
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $query = $this->placeOrderQuery($maskedQuoteId);

        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertEquals(1, count($response['placeOrder']['errors']));
        $this->assertEquals('UNABLE_TO_PLACE_ORDER', $response['placeOrder']['errors'][0]['code']);
        $this->assertEquals(
            'The shipping method is missing. Select the shipping method and try again.',
            $response['placeOrder']['errors'][0]['message']
        );
    }

    #[
        Config('carriers/flatrate/active', '1', 'store', 'default'),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Indexer::class, as: 'indexer'),
        DataFixture(AsyncMode::class),
        DataFixture(Customer::class, ['email' => 'customer@example.com'], as: 'customer'),
        DataFixture(
            CustomerCart::class,
            [
                'customer_id' => '$customer.id$',
                'reserved_order_id' => 'test_quote'
            ],
            'cart'
        ),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(QuoteIdMask::class, ['cart_id' => '$cart.id$'], 'quoteIdMask'),
    ]
    public function testAsyncPlaceOrderWithNoBillingAddress()
    {
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $query = $this->placeOrderQuery($maskedQuoteId);

        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertEquals(1, count($response['placeOrder']['errors']));
        $this->assertEquals('UNABLE_TO_PLACE_ORDER', $response['placeOrder']['errors'][0]['code']);
        $this->assertStringContainsString(
            'Please check the billing address information.',
            $response['placeOrder']['errors'][0]['message']
        );
    }

    #[
        Config('carriers/flatrate/active', '1', 'store', 'default'),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Indexer::class, as: 'indexer'),
        DataFixture(AsyncMode::class),
        DataFixture(Customer::class, ['email' => 'customer@example.com'], as: 'customer'),
        DataFixture(
            CustomerCart::class,
            [
                'customer_id' => '$customer.id$',
                'reserved_order_id' => 'test_quote'
            ],
            'cart'
        ),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(QuoteIdMask::class, ['cart_id' => '$cart.id$'], 'quoteIdMask'),
    ]
    public function testAsyncPlaceOrderWithNoPaymentMethodOnCart()
    {
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $query = $this->placeOrderQuery($maskedQuoteId);

        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertEquals(1, count($response['placeOrder']['errors']));
        $this->assertEquals('UNABLE_TO_PLACE_ORDER', $response['placeOrder']['errors'][0]['code']);
        $this->assertEquals(
            'Enter a valid payment method and try again.',
            $response['placeOrder']['errors'][0]['message']
        );
    }

    #[
        Config('carriers/flatrate/active', '1', 'store', 'default'),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Indexer::class, as: 'indexer'),
        DataFixture(AsyncMode::class),
        DataFixture(Customer::class, ['email' => 'customer@example.com'], as: 'customer'),
        DataFixture(
            CustomerCart::class,
            [
                'customer_id' => '$customer.id$',
                'reserved_order_id' => 'test_quote'
            ],
            'cart'
        ),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(
            ProductStockFixture::class,
            [
                'prod_id' => '$product.id$',
                'is_in_stock' => 0,
                'prod_qty' => 0
            ],
            'prodStock'
        ),
        DataFixture(QuoteIdMask::class, ['cart_id' => '$cart.id$'], 'quoteIdMask'),
    ]
    public function testPlaceOrderWithOutOfStockProduct()
    {
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $query = $this->placeOrderQuery($maskedQuoteId);

        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertEquals(1, count($response['placeOrder']['errors']));
        $this->assertEquals('UNABLE_TO_PLACE_ORDER', $response['placeOrder']['errors'][0]['code']);
        $this->assertEquals(
            'Some of the products are out of stock.',
            $response['placeOrder']['errors'][0]['message']
        );
    }

    /**
     * @param string $maskedQuoteId
     * @return string
     */
    private function placeOrderQuery(string $maskedQuoteId): string
    {
        return <<<QUERY
mutation {
  placeOrder(input: {cart_id: "{$maskedQuoteId}"}) {
    order {
      order_number
    }
    errors {
      message
      code
    }
  }
}
QUERY;
    }

    /**
     * @param string $username
     * @param string $password
     * @return array
     * @throws \Magento\Framework\Exception\AuthenticationException
     */
    private function getHeaderMap(string $username = 'customer@example.com', string $password = 'password'): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($username, $password);
        $headerMap = ['Authorization' => 'Bearer ' . $customerToken];
        return $headerMap;
    }

    /**
     * Get customer order query
     *
     * @param string $orderNumber
     * @return array
     */
    private function getCustomerOrderQuery($orderNumber): array
    {
        $query =
            <<<QUERY
{
  customer
  {
   orders(filter:{number:{eq:"{$orderNumber}"}}) {
    total_count
    items
    {
      id
      number
      status
      order_date
      shipping_method
      payment_methods
      {
        name
        type
        additional_data
        {
         name
         value
         }
      }
      shipping_address {
         ... address
      }
      billing_address {
      ... address
      }
      items{
        quantity_ordered
        product_sku
        product_name
        product_sale_price{currency value}
      }
      total {
        base_grand_total {
          value
          currency
           }
        grand_total {
           value
           currency
           }
        subtotal {
           value
           currency
           }
        }
    }
   }
 }
}

fragment address on OrderAddress {
   firstname
   lastname
   city
   company
   country_code
   postcode
   street
   region
   telephone
   }
QUERY;
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getHeaderMap()
        );

        $this->assertArrayHasKey('orders', $response['customer']);
        $this->assertArrayHasKey('items', $response['customer']['orders']);
        return $response;
    }

    /**
     * @param string $cartId
     * @param string $method
     * @return void
     */
    private function setPaymentMethod(string $cartId, string $paymentMethod): void
    {
        $query = <<<QUERY
mutation {
  setPaymentMethodOnCart(
    input: {
      cart_id: "{$cartId}"
      payment_method: {
        code: "{$paymentMethod}"
      }
    }
  ) {
    cart {
      selected_payment_method { code }
    }
  }
}
QUERY;
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        //verify that the appropriate payment method is set on the cart
        $this->assertEquals(
            $paymentMethod,
            $response['setPaymentMethodOnCart']['cart']['selected_payment_method']['code']
        );
    }

    /**
     * Run the placeOrderProcessor consumer
     */
    private function runPlaceOrderProcessorConsumer()
    {
        $publisherConsumerController = Bootstrap::getObjectManager()->create(
            PublisherConsumerController::class,
            [
                'consumers' => ['placeOrderProcessor'],
                'maxMessages' => 1,
            ]
        );
        try {
            $publisherConsumerController->initialize();
            $publisherConsumerController->waitForAsynchronousResult(
                fn() => !$publisherConsumerController->getConsumersProcessIds()['placeOrderProcessor']
            );
        } catch (EnvironmentPreconditionException $e) {
            $this->markTestSkipped($e->getMessage());
        } finally {
            $publisherConsumerController->stopConsumers();
        }
    }

    /**
     *  Assert billing address fields
     *
     * @param $actualBillingResponse
     */
    private function assertBillingAddressFields($actualBillingAddressInResponse)
    {
        $assertionMap=[
            ['response_field' => 'firstname', 'expected_value' => 'John'],
            ['response_field' => 'lastname', 'expected_value' => 'Smith'],
            ['response_field' => 'city', 'expected_value' => 'CityM'],
            ['response_field' => 'company', 'expected_value' => 'CompanyName'],
            ['response_field' => 'country_code', 'expected_value' => 'US'],
            ['response_field' => 'postcode', 'expected_value' => '75477'],
            ['response_field' => 'street', 'expected_value' => [0 => 'Green str, 67']],
            ['response_field' => 'region', 'expected_value' => 'Alabama'],
            ['response_field' => 'telephone', 'expected_value' => '3468676']
        ];
        $this->assertResponseFields($actualBillingAddressInResponse, $assertionMap);
    }

    /**
     *  Assert shipping address fields
     *
     * @param $actualBillingResponse
     */
    private function assertShippingAddressFields($actualShippingAdressInResponse)
    {
        $assertionMap=[
            ['response_field' => 'firstname', 'expected_value' => 'John'],
            ['response_field' => 'lastname', 'expected_value' => 'Smith'],
            ['response_field' => 'city', 'expected_value' => 'CityM'],
            ['response_field' => 'company', 'expected_value' => 'CompanyName'],
            ['response_field' => 'country_code', 'expected_value' => 'US'],
            ['response_field' => 'postcode', 'expected_value' => '75477'],
            ['response_field' => 'street', 'expected_value' => [0 => 'Green str, 67']],
            ['response_field' => 'region', 'expected_value' => 'Alabama'],
            ['response_field' => 'telephone', 'expected_value' => '3468676']
        ];
        $this->assertResponseFields($actualShippingAdressInResponse, $assertionMap);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);

        $orderCollection = $this->orderCollectionFactory->create();
        foreach ($orderCollection as $order) {
            $this->orderRepository->delete($order);
        }
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);

        parent::tearDown();
    }
}
