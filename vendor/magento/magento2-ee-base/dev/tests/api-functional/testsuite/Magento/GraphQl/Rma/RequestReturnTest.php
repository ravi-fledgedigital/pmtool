<?php
/************************************************************************
 * Copyright 2020 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ***********************************************************************
 */
declare(strict_types=1);

namespace Magento\GraphQl\Rma;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Test\Fixture\Customer;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Eav\Test\Fixture\AttributeOption as AttributeOptionFixture;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Indexer\Test\Fixture\Indexer;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\Rma\Api\RmaAttributesManagementInterface;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Rma\Test\Fixture\Rma as RmaFixture;
use Magento\Rma\Test\Fixture\RmaItemAttribute as RmaItemAttributeFixture;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Test\Fixture\Invoice as InvoiceFixture;
use Magento\Sales\Test\Fixture\Shipment as ShipmentFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for request return mutation
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RequestReturnTest extends GraphQlAbstract
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var RmaRepositoryInterface
     */
    private $rmaRepository;

    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * Setup
     */
    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->getCustomerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->rmaRepository = $this->objectManager->get(RmaRepositoryInterface::class);
        $this->orderFactory = $this->objectManager->get(OrderInterfaceFactory::class);
        $this->idEncoder = $this->objectManager->get(Uid::class);
        $this->serializer = $this->objectManager->get(SerializerInterface::class);
        $this->connection = $this->objectManager->get(ResourceConnection::class)->getConnection();
    }

    /**
     * Test request return with unauthorized customer
     */
    #[
        Config("sales/magento_rma/enabled", 1),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'attribute_code' => 'entered_item_attribute',
                'frontend_label' => ['entered_item_attribute'],
            ],
            'varchar_attribute'
        ),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'frontend_input' => 'multiselect',
                'source_model' => Table::class,
                'backend_model' => ArrayBackend::class,
                'attribute_code' => 'selected_rma_item_attribute',
                'frontend_label' => ['selected_rma_item_attribute'],
            ],
            'multiselect_attribute'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 20,
                'label' => 'first',
                'is_default' => true
            ],
            'multiselect_attribute_option_1'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 10,
                'label' => 'second'
            ],
            'multiselect_attribute_option_2'
        ),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Indexer::class),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$quote.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(
            ShipmentFixture::class,
            [
                'order_id' => '$order.id$',
                'items' => [
                    ['product_id' => '$product.id$'],
                ]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testUnauthorized()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');

        $orderNumber = DataFixtureStorageManager::getStorage()->get('order')->getIncrementId();
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderUid = $this->idEncoder->encode((string)$order->getEntityId());

        $items = $this->prepareItems($order);

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      contact_email: "returnemail@magento.com"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation($mutation);
    }

    /**
     * Test request return
     */
    #[
        Config("sales/magento_rma/enabled", 1),
        Config("sales/magento_rma/use_store_address", 0),
        Config("sales/magento_rma/store_name", "test"),
        Config("sales/magento_rma/address", "street"),
        Config("sales/magento_rma/address1", "1"),
        Config("sales/magento_rma/region_id", "wrong region"),
        Config("sales/magento_rma/city", "Montgomery"),
        Config("sales/magento_rma/zip", "12345"),
        Config("sales/magento_rma/country_id", "US"),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'attribute_code' => 'entered_item_attribute',
                'frontend_label' => ['entered_item_attribute'],
            ],
            'varchar_attribute'
        ),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'frontend_input' => 'multiselect',
                'source_model' => Table::class,
                'backend_model' => ArrayBackend::class,
                'attribute_code' => 'selected_rma_item_attribute',
                'frontend_label' => ['selected_rma_item_attribute'],
            ],
            'multiselect_attribute'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 20,
                'label' => 'first',
                'is_default' => true
            ],
            'multiselect_attribute_option_1'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 10,
                'label' => 'second'
            ],
            'multiselect_attribute_option_2'
        ),
        DataFixture(ProductFixture::class, as: 'product1'),
        DataFixture(ProductFixture::class, as: 'product2'),
        DataFixture(Indexer::class),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product1.id$', 'qty' => 1]),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product2.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$quote.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(
            ShipmentFixture::class,
            [
                'order_id' => '$order.id$',
                'items' => [
                    ['product_id' => '$product1.id$'],
                    ['product_id' => '$product2.id$'],
                ]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testRequestReturn()
    {
        $product1 = DataFixtureStorageManager::getStorage()->get('product1');
        $product2 = DataFixtureStorageManager::getStorage()->get('product2');
        $products = [
            [
                'name' => $product1->getName(),
                'sku' => $product1->getSku(),
            ],
            [
                'name' => $product2->getName(),
                'sku' => $product2->getSku(),
            ],
        ];

        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $customerEmail = $customer->getEmail();
        $customerName = $customer->getName();
        $orderNumber = DataFixtureStorageManager::getStorage()->get('order')->getIncrementId();
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderUid = $this->idEncoder->encode((string)$order->getEntityId());
        $contactEmail = 'returnemail@magento.com';

        $items = $this->prepareItems($order);

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      contact_email: "{$contactEmail}"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      status
      customer{email}
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );

        self::assertEqualsIgnoringCase(Status::STATE_PENDING, $response['requestReturn']['return']['status']);
        $this->assertRmaItems($response['requestReturn']['return']['items'], $products);
        $this->assertRmaComments($response['requestReturn']['return']['comments'], $customerName);
        self::assertEquals($contactEmail, $response['requestReturn']['return']['customer']['email']);
    }

    /**
     * Test request return with wrong order id
     */
    #[
        Config("sales/magento_rma/enabled", 1),
        Config("sales/magento_rma/use_store_address", 0),
        Config("sales/magento_rma/store_name", "test"),
        Config("sales/magento_rma/address", "street"),
        Config("sales/magento_rma/address1", "1"),
        Config("sales/magento_rma/region_id", "wrong region"),
        Config("sales/magento_rma/city", "Montgomery"),
        Config("sales/magento_rma/zip", "12345"),
        Config("sales/magento_rma/country_id", "US"),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'attribute_code' => 'entered_item_attribute',
                'frontend_label' => ['entered_item_attribute'],
            ],
            'varchar_attribute'
        ),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'frontend_input' => 'multiselect',
                'source_model' => Table::class,
                'backend_model' => ArrayBackend::class,
                'attribute_code' => 'selected_rma_item_attribute',
                'frontend_label' => ['selected_rma_item_attribute'],
            ],
            'multiselect_attribute'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 20,
                'label' => 'first',
                'is_default' => true
            ],
            'multiselect_attribute_option_1'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 10,
                'label' => 'second'
            ],
            'multiselect_attribute_option_2'
        ),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Indexer::class),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$quote.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(
            ShipmentFixture::class,
            [
                'order_id' => '$order.id$',
                'items' => [
                    ['product_id' => '$product.id$'],
                ]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testWithWrongOrderId()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The entity that was requested doesn\'t exist. Verify the entity and try again.');

        $customerEmail = DataFixtureStorageManager::getStorage()->get('customer')->getEmail();
        $orderNumber = DataFixtureStorageManager::getStorage()->get('order')->getIncrementId();
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderId = $order->getEntityId() + 10;
        $orderUid = $this->idEncoder->encode((string)$orderId);

        $items = $this->prepareItems($order);

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      contact_email: "returnemail@magento.com"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      status
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Test request return with not encoded order id
     */
    #[
        Config("sales/magento_rma/enabled", 1),
        Config("sales/magento_rma/use_store_address", 0),
        Config("sales/magento_rma/store_name", "test"),
        Config("sales/magento_rma/address", "street"),
        Config("sales/magento_rma/address1", "1"),
        Config("sales/magento_rma/region_id", "wrong region"),
        Config("sales/magento_rma/city", "Montgomery"),
        Config("sales/magento_rma/zip", "12345"),
        Config("sales/magento_rma/country_id", "US"),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'attribute_code' => 'entered_item_attribute',
                'frontend_label' => ['entered_item_attribute'],
            ],
            'varchar_attribute'
        ),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'frontend_input' => 'multiselect',
                'source_model' => Table::class,
                'backend_model' => ArrayBackend::class,
                'attribute_code' => 'selected_rma_item_attribute',
                'frontend_label' => ['selected_rma_item_attribute'],
            ],
            'multiselect_attribute'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 20,
                'label' => 'first',
                'is_default' => true
            ],
            'multiselect_attribute_option_1'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 10,
                'label' => 'second'
            ],
            'multiselect_attribute_option_2'
        ),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Indexer::class),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$quote.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(
            ShipmentFixture::class,
            [
                'order_id' => '$order.id$',
                'items' => [
                    ['product_id' => '$product.id$'],
                ]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testWithNotEncodedOrderId()
    {
        $customerEmail = DataFixtureStorageManager::getStorage()->get('customer')->getEmail();
        $orderNumber = DataFixtureStorageManager::getStorage()->get('order')->getIncrementId();
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderId = $order->getEntityId();
        $items = $this->prepareItems($order);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Value of uid \"{$orderId}\" is incorrect.");

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderId}"
      contact_email: "returnemail@magento.com"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      status
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Test request return without required fields
     */
    #[
        Config("sales/magento_rma/enabled", 1),
        Config("sales/magento_rma/use_store_address", 0),
        Config("sales/magento_rma/store_name", "test"),
        Config("sales/magento_rma/address", "street"),
        Config("sales/magento_rma/address1", "1"),
        Config("sales/magento_rma/region_id", "wrong region"),
        Config("sales/magento_rma/city", "Montgomery"),
        Config("sales/magento_rma/zip", "12345"),
        Config("sales/magento_rma/country_id", "US"),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'attribute_code' => 'entered_item_attribute',
                'frontend_label' => ['entered_item_attribute'],
            ],
            'varchar_attribute'
        ),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'frontend_input' => 'multiselect',
                'source_model' => Table::class,
                'backend_model' => ArrayBackend::class,
                'attribute_code' => 'selected_rma_item_attribute',
                'frontend_label' => ['selected_rma_item_attribute'],
            ],
            'multiselect_attribute'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 20,
                'label' => 'first',
                'is_default' => true
            ],
            'multiselect_attribute_option_1'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 10,
                'label' => 'second'
            ],
            'multiselect_attribute_option_2'
        ),
        DataFixture(ProductFixture::class, as: 'product1'),
        DataFixture(ProductFixture::class, as: 'product2'),
        DataFixture(Indexer::class),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product1.id$', 'qty' => 1]),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product2.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$quote.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(
            ShipmentFixture::class,
            [
                'order_id' => '$order.id$',
                'items' => [
                    ['product_id' => '$product1.id$'],
                    ['product_id' => '$product2.id$'],
                ]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testWithoutRequiredFields()
    {
        $product1 = DataFixtureStorageManager::getStorage()->get('product1');
        $product2 = DataFixtureStorageManager::getStorage()->get('product2');
        $products = [
            [
                'name' => $product1->getName(),
                'sku' => $product1->getSku(),
            ],
            [
                'name' => $product2->getName(),
                'sku' => $product2->getSku(),
            ],
        ];

        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $customerEmail = $customer->getEmail();
        $customerName = $customer->getName();
        $orderNumber = DataFixtureStorageManager::getStorage()->get('order')->getIncrementId();
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderUid = $this->idEncoder->encode((string)$order->getEntityId());

        $items = $this->prepareItems($order);

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      items: [{$items}]
    }
  ) {
    return {
      uid
      status
      customer{email}
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );

        self::assertEqualsIgnoringCase(Status::STATE_PENDING, $response['requestReturn']['return']['status']);
        $this->assertRmaItems($response['requestReturn']['return']['items'], $products);
        self::assertEmpty($response['requestReturn']['return']['comments'], $customerName);
        self::assertEquals($customerEmail, $response['requestReturn']['return']['customer']['email']);
    }

    /**
     * Test request return with unauthorized customer
     */
    #[
        Config("sales/magento_rma/enabled", 1),
        Config("sales/magento_rma/use_store_address", 0),
        Config("sales/magento_rma/store_name", "test"),
        Config("sales/magento_rma/address", "street"),
        Config("sales/magento_rma/address1", "1"),
        Config("sales/magento_rma/region_id", "wrong region"),
        Config("sales/magento_rma/city", "Montgomery"),
        Config("sales/magento_rma/zip", "12345"),
        Config("sales/magento_rma/country_id", "US"),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'attribute_code' => 'entered_item_attribute',
                'frontend_label' => ['entered_item_attribute'],
            ],
            'varchar_attribute'
        ),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'frontend_input' => 'multiselect',
                'source_model' => Table::class,
                'backend_model' => ArrayBackend::class,
                'attribute_code' => 'selected_rma_item_attribute',
                'frontend_label' => ['selected_rma_item_attribute'],
            ],
            'multiselect_attribute'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 20,
                'label' => 'first',
                'is_default' => true
            ],
            'multiselect_attribute_option_1'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 10,
                'label' => 'second'
            ],
            'multiselect_attribute_option_2'
        ),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Indexer::class),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$quote.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(
            ShipmentFixture::class,
            [
                'order_id' => '$order.id$',
                'items' => [
                    ['product_id' => '$product.id$'],
                ]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testWithWrongOrderItemId()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You cannot return');

        $customerEmail = DataFixtureStorageManager::getStorage()->get('customer')->getEmail();
        $orderNumber = DataFixtureStorageManager::getStorage()->get('order')->getIncrementId();
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderUid = $this->idEncoder->encode((string)$order->getEntityId());

        $items = $this->prepareItems($order, true);

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      contact_email: "returnemail@magento.com"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      status
      customer{email}
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Test request return with not encoded order item id
     */
    #[
        Config("sales/magento_rma/enabled", 1),
        Config("sales/magento_rma/use_store_address", 0),
        Config("sales/magento_rma/store_name", "test"),
        Config("sales/magento_rma/address", "street"),
        Config("sales/magento_rma/address1", "1"),
        Config("sales/magento_rma/region_id", "wrong region"),
        Config("sales/magento_rma/city", "Montgomery"),
        Config("sales/magento_rma/zip", "12345"),
        Config("sales/magento_rma/country_id", "US"),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'attribute_code' => 'entered_item_attribute',
                'frontend_label' => ['entered_item_attribute'],
            ],
            'varchar_attribute'
        ),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'frontend_input' => 'multiselect',
                'source_model' => Table::class,
                'backend_model' => ArrayBackend::class,
                'attribute_code' => 'selected_rma_item_attribute',
                'frontend_label' => ['selected_rma_item_attribute'],
            ],
            'multiselect_attribute'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 20,
                'label' => 'first',
                'is_default' => true
            ],
            'multiselect_attribute_option_1'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 10,
                'label' => 'second'
            ],
            'multiselect_attribute_option_2'
        ),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Indexer::class),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$quote.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(
            ShipmentFixture::class,
            [
                'order_id' => '$order.id$',
                'items' => [
                    ['product_id' => '$product.id$'],
                ]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testWithNotEncodedOrderItemId()
    {
        $customerEmail = DataFixtureStorageManager::getStorage()->get('customer')->getEmail();
        $orderNumber = DataFixtureStorageManager::getStorage()->get('order')->getIncrementId();
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderUid = $this->idEncoder->encode((string)$order->getEntityId());

        $items = $this->prepareItems($order, false, false);
        $item = current($order->getItems());
        $itemId = $item->getItemId();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Value of uid \"{$itemId}\" is incorrect.");

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      contact_email: "returnemail@magento.com"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      status
      customer{email}
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Test request return with bigger item's quantity than ordered
     */
    #[
        Config("sales/magento_rma/enabled", 1),
        Config("sales/magento_rma/use_store_address", 0),
        Config("sales/magento_rma/store_name", "test"),
        Config("sales/magento_rma/address", "street"),
        Config("sales/magento_rma/address1", "1"),
        Config("sales/magento_rma/region_id", "wrong region"),
        Config("sales/magento_rma/city", "Montgomery"),
        Config("sales/magento_rma/zip", "12345"),
        Config("sales/magento_rma/country_id", "US"),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'attribute_code' => 'entered_item_attribute',
                'frontend_label' => ['entered_item_attribute'],
            ],
            'varchar_attribute'
        ),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'frontend_input' => 'multiselect',
                'source_model' => Table::class,
                'backend_model' => ArrayBackend::class,
                'attribute_code' => 'selected_rma_item_attribute',
                'frontend_label' => ['selected_rma_item_attribute'],
            ],
            'multiselect_attribute'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 20,
                'label' => 'first',
                'is_default' => true
            ],
            'multiselect_attribute_option_1'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 10,
                'label' => 'second'
            ],
            'multiselect_attribute_option_2'
        ),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Indexer::class),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$quote.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(
            ShipmentFixture::class,
            [
                'order_id' => '$order.id$',
                'items' => [
                    ['product_id' => '$product.id$'],
                ]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testWithBiggerQuantityToReturn()
    {
        $product = DataFixtureStorageManager::getStorage()->get('product')->getName();
        $customerEmail = DataFixtureStorageManager::getStorage()->get('customer')->getEmail();
        $orderNumber = DataFixtureStorageManager::getStorage()->get('order')->getIncrementId();
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderUid = $this->idEncoder->encode((string)$order->getEntityId());
        $items = $this->prepareItems($order, false, true, 1000);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("A quantity of {$product} is greater than you can return.");

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      contact_email: "returnemail@magento.com"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      status
      customer{email}
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Test request return with less than 1 quantity
     */
    #[
        Config("sales/magento_rma/enabled", 1),
        Config("sales/magento_rma/use_store_address", 0),
        Config("sales/magento_rma/store_name", "test"),
        Config("sales/magento_rma/address", "street"),
        Config("sales/magento_rma/address1", "1"),
        Config("sales/magento_rma/region_id", "wrong region"),
        Config("sales/magento_rma/city", "Montgomery"),
        Config("sales/magento_rma/zip", "12345"),
        Config("sales/magento_rma/country_id", "US"),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'attribute_code' => 'entered_item_attribute',
                'frontend_label' => ['entered_item_attribute'],
            ],
            'varchar_attribute'
        ),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'frontend_input' => 'multiselect',
                'source_model' => Table::class,
                'backend_model' => ArrayBackend::class,
                'attribute_code' => 'selected_rma_item_attribute',
                'frontend_label' => ['selected_rma_item_attribute'],
            ],
            'multiselect_attribute'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 20,
                'label' => 'first',
                'is_default' => true
            ],
            'multiselect_attribute_option_1'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 10,
                'label' => 'second'
            ],
            'multiselect_attribute_option_2'
        ),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Indexer::class),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$quote.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(
            ShipmentFixture::class,
            [
                'order_id' => '$order.id$',
                'items' => [
                    ['product_id' => '$product.id$'],
                ]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testWithLessQuantityToReturn()
    {
        $customerEmail = DataFixtureStorageManager::getStorage()->get('customer')->getEmail();
        $orderNumber = DataFixtureStorageManager::getStorage()->get('order')->getIncrementId();
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderUid = $this->idEncoder->encode((string)$order->getEntityId());
        $items = $this->prepareItems($order, false, true, 0);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("You cannot return less than 1 product.");

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      contact_email: "returnemail@magento.com"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      status
      customer{email}
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Test request return with disabled RMA
     */
    #[
        Config("sales/magento_rma/enabled", 0),
        Config("sales/magento_rma/enabled_on_product", 0),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'attribute_code' => 'entered_item_attribute',
                'frontend_label' => ['entered_item_attribute'],
            ],
            'varchar_attribute'
        ),
        DataFixture(
            RmaItemAttributeFixture::class,
            [
                'frontend_input' => 'multiselect',
                'source_model' => Table::class,
                'backend_model' => ArrayBackend::class,
                'attribute_code' => 'selected_rma_item_attribute',
                'frontend_label' => ['selected_rma_item_attribute'],
            ],
            'multiselect_attribute'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 20,
                'label' => 'first',
                'is_default' => true
            ],
            'multiselect_attribute_option_1'
        ),
        DataFixture(
            AttributeOptionFixture::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$multiselect_attribute.attribute_code$',
                'sort_order' => 10,
                'label' => 'second'
            ],
            'multiselect_attribute_option_2'
        ),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Indexer::class),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$quote.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(
            ShipmentFixture::class,
            [
                'order_id' => '$order.id$',
                'items' => [
                    ['product_id' => '$product.id$'],
                ]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testWithDisabledRma()
    {
        $customerEmail = DataFixtureStorageManager::getStorage()->get('customer')->getEmail();
        $orderNumber = DataFixtureStorageManager::getStorage()->get('order')->getIncrementId();
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderUid = $this->idEncoder->encode((string)$order->getEntityId());
        $items = $this->prepareItems($order);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("RMA is disabled.");

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      contact_email: "returnemail@magento.com"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      status
      customer{email}
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Assert RMA items
     *
     * @param array $actualItems
     * @param array $products
     * @throws GraphQlInputException
     */
    private function assertRmaItems(array $actualItems, array $products): void
    {
        $expectedItems = [
            [
                'quantity' => 0,
                'request_quantity' => 1,
                'order_item' => [
                    'product_sku' => $products[0]['sku'],
                    'product_name' => $products[0]['name']
                ],
                'status' => Status::STATE_PENDING,
                'custom_attributes' => [
                    [
                        'value' => "Exchange",
                        'label' => 'Resolution',
                    ],
                    [
                        'value' => "Opened",
                        'label' => 'Item Condition',
                    ],
                    [
                        'value' => 'Wrong Color',
                        'label' => 'Reason to Return',
                    ],
                    [
                        'label' => 'entered_item_attribute',
                        'value' => 'Custom attribute value'
                    ],
                    [
                        'label' => 'selected_rma_item_attribute',
                        'value' => 'second',
                    ],
                ]
            ],
            [
                'quantity' => 0,
                'request_quantity' => 1,
                'order_item' => [
                    'product_sku' => $products[1]['sku'],
                    'product_name' => $products[1]['name']
                ],
                'status' => Status::STATE_PENDING,
                'custom_attributes' => [
                    [
                        'value' => 'Exchange',
                        'label' => 'Resolution',
                    ],
                    [
                        'value' => 'Opened',
                        'label' => 'Item Condition',
                    ],
                    [
                        'value' => 'Wrong Color',
                        'label' => 'Reason to Return',
                    ],
                    [
                        'label' => 'entered_item_attribute',
                        'value' => 'Custom attribute value',
                    ],
                    [
                        'label' => 'selected_rma_item_attribute',
                        'value' => 'second',
                    ],
                ]
            ]
        ];

        foreach ($expectedItems as $key => $expectedItem) {
            $this->assertItem($expectedItem, $actualItems[$key]);
        }
    }

    /**
     * Assert RMA item
     *
     * @param array $expectedItem
     * @param array $actualItem
     * @throws GraphQlInputException
     */
    private function assertItem(array $expectedItem, array $actualItem): void
    {
        self::assertIsNumeric($this->idEncoder->decode($actualItem['uid']));
        self::assertEquals($expectedItem['quantity'], $actualItem['quantity']);
        self::assertEquals($expectedItem['request_quantity'], $actualItem['request_quantity']);
        self::assertEqualsCanonicalizing($expectedItem['order_item'], $actualItem['order_item']);
        self::assertEqualsIgnoringCase(Status::STATE_PENDING, $actualItem['status']);

        foreach ($expectedItem['custom_attributes'] as $key => $customAttribute) {
            $this->assertCustomAttribute($customAttribute, $actualItem['custom_attributes'][$key]);
        }
    }

    /**
     * Assert RMA item custom attribute
     *
     * @param array $expectedAttribute
     * @param array $actualAttribute
     * @throws GraphQlInputException
     */
    private function assertCustomAttribute(array $expectedAttribute, array $actualAttribute): void
    {
        self::assertIsNumeric($this->idEncoder->decode($actualAttribute['uid']));
        self::assertEquals($expectedAttribute['label'], $actualAttribute['label']);
        self::assertEquals($this->serializer->serialize($expectedAttribute['value']), $actualAttribute['value']);
    }

    /**
     * Assert RMA comments
     *
     * @param array $actualComments
     * @param string $customerName
     */
    private function assertRmaComments(array $actualComments, string $customerName): void
    {
        $expectedComments = [
            [
                'created_at' => date('Y-m-d H:i:s'),
                'author_name' => 'Customer Service',
                'text' => 'We placed your Return request.'
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'author_name' => $customerName,
                'text' => 'Return comment'
            ]
        ];

        foreach ($expectedComments as $key => $expectedComment) {
            $this->assertComment($expectedComment, $actualComments[$key]);
        }
    }

    /**
     * Assert RMA comment
     *
     * @param array $expectedComment
     * @param array $actualComment
     */
    private function assertComment(array $expectedComment, array $actualComment): void
    {
        self::assertEqualsWithDelta(
            strtotime($expectedComment['created_at']),
            strtotime($actualComment['created_at']),
            1
        );
        self::assertEquals($expectedComment['author_name'], $actualComment['author_name']);
        self::assertEquals($expectedComment['text'], $actualComment['text']);
    }

    /**
     * Prepare items for mutation
     *
     * @param OrderInterface $order
     * @param bool $isWrong
     * @param bool $isEncoded
     * @param int $qty
     * @return string
     */
    private function prepareItems(
        OrderInterface $order,
        bool $isWrong = false,
        bool $isEncoded = true,
        int $qty = 1
    ): string {
        $selectedValue = 'second';
        $selectedAttribute = 'selected_rma_item_attribute';
        $encodedSelectedValueId = $this->idEncoder->encode(
            $this->getOptionValueIdByValue(
                $selectedValue,
                $selectedAttribute
            )
        );

        $encodedResolutionValueId = $this->idEncoder->encode('4');
        $encodedConditionValueId = $this->idEncoder->encode('8');
        $encodedReasonValueId = $this->idEncoder->encode('10');

        $items = '';
        foreach ($order->getItems() as $item) {
            $itemId = $item->getItemId();
            if ($isWrong) {
                $itemId += 10;
            }
            if ($isEncoded) {
                $itemId = $this->idEncoder->encode((string)$itemId);
            }

            $items .= <<<ITEM
{
          order_item_uid: "{$itemId}",
          quantity_to_return: {$qty},
          selected_custom_attributes: [
          {attribute_code: "{$selectedAttribute}", value: "{$encodedSelectedValueId}"}
          {attribute_code: "resolution", value: "{$encodedResolutionValueId}"}
          {attribute_code: "condition", value: "{$encodedConditionValueId}"}
          {attribute_code: "reason", value: "{$encodedReasonValueId}"}
          ],
          entered_custom_attributes: [{attribute_code: "entered_item_attribute", value: "Custom attribute value"}]
        }
ITEM;
        }

        return $items;
    }

    /**
     * Get option value id by value
     *
     * @param string $value
     * @param string $attributeCode
     * @return string
     */
    private function getOptionValueIdByValue(string $value, string $attributeCode): string
    {
        $select = $this->connection->select()
            ->from(
                ['eaov' => $this->connection->getTableName('eav_attribute_option_value')],
                'eaov.option_id'
            )
            ->joinInner(
                ['eao' =>$this->connection->getTableName('eav_attribute_option')],
                'eao.option_id = eaov.option_id'
            )
            ->joinInner(
                ['ea' =>$this->connection->getTableName('eav_attribute')],
                'ea.attribute_id = eao.attribute_id'
            )
            ->where('eaov.value = ?', $value)
            ->where('ea.attribute_code = ?', $attributeCode)
            ->where('ea.entity_type_id = ?', 9);

        return $this->connection->fetchOne($select);
    }
}
