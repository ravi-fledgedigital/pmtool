<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\AsyncOrder\Guest;

use Magento\AsyncOrder\Test\Fixture\AsyncMode;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetGuestEmail as SetGuestEmailFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Framework\Registry;
use Magento\Indexer\Test\Fixture\Indexer;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for asynchronous checkout by guest
 */
class AsyncPlaceOrderTest extends GraphQlAbstract
{
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
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    private $quoteIdToMaskedQuoteIdInterface;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->orderCollectionFactory = $objectManager->get(CollectionFactory::class);
        $this->orderRepository = $objectManager->get(OrderRepositoryInterface::class);
        $this->orderFactory = $objectManager->get(OrderFactory::class);
        $this->registry = Bootstrap::getObjectManager()->get(Registry::class);
        $this->quoteIdToMaskedQuoteIdInterface = $objectManager->get(QuoteIdToMaskedQuoteIdInterface::class);
    }

    #[
        Config('carriers/flatrate/active', '1', 'store', 'default'),
        Config('payment/checkmo/active', '1', 'store', 'default'),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Indexer::class, as: 'indexer'),
        DataFixture(AsyncMode::class),
        DataFixture(GuestCartFixture::class, ['reserved_order_id' => 'test_quote'], as: 'cart'),
        DataFixture(SetGuestEmailFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$']),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
    ]
    public function testAsyncPlaceOrder()
    {
        $cart = DataFixtureStorageManager::getStorage()->get('cart');
        $maskedQuoteId = $this->quoteIdToMaskedQuoteIdInterface->execute((int) $cart->getId());

        $query = $this->placeOrderQuery($maskedQuoteId);
        $response = $this->graphQlMutation($query);

        $this->assertArrayHasKey('placeOrder', $response);
        $this->assertArrayHasKey('order_number', $response['placeOrder']['order']);
        $orderNumber = $response['placeOrder']['order']['order_number'];
        $order = $this->orderFactory->create();
        $order->loadByIncrementId($orderNumber);
        $this->assertEquals('received', $order->getStatus());
        // run the placeOrder consumer
        $this->runPlaceOrderProcessorConsumer();
        $this->assertEquals('pending', $order->loadByIncrementId($orderNumber)->getStatus());
        $this->assertTrue(((boolean)$order->getEmailSent()));
    }

    #[
        Config('carriers/flatrate/active', '1', 'store', 'default'),
        Config('payment/checkmo/active', '1', 'store', 'default'),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Indexer::class, as: 'indexer'),
        DataFixture(AsyncMode::class),
        DataFixture(GuestCartFixture::class, ['reserved_order_id' => 'test_quote'], as: 'cart'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$']),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
    ]
    public function testAsyncPlaceOrderWithNoGuestEmailOnCart()
    {
        $cart = DataFixtureStorageManager::getStorage()->get('cart');
        $maskedQuoteId = $this->quoteIdToMaskedQuoteIdInterface->execute((int) $cart->getId());
        $query = $this->placeOrderQuery($maskedQuoteId);

        $response = $this->graphQlMutation($query);
        $this->assertEquals(1, count($response['placeOrder']['errors']));
        $this->assertEquals('GUEST_EMAIL_MISSING', $response['placeOrder']['errors'][0]['code']);
        $this->assertEquals(
            'Guest email for cart is missing.',
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
     * Run the placeOrderProcessor consumer
     */
    private function runPlaceOrderProcessorConsumer()
    {
        $appDir = dirname(Bootstrap::getInstance()->getAppTempDir());
        $out = '';
        // phpcs:ignore Magento2.Security.InsecureFunction
        exec("php -f {$appDir}/bin/magento queue:consumers:start placeOrderProcessor --max-messages=1", $out);
        //wait until the queue message status changes to "COMPLETE"
        sleep(30);
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
