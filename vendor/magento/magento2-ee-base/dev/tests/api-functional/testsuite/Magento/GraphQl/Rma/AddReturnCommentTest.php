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
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\ObjectManagerInterface;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Indexer\Test\Fixture\Indexer;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma;
use Magento\Rma\Test\Fixture\Rma as RmaFixture;
use Magento\Rma\Test\Fixture\RmaItem as RmaItemFixture;
use Magento\Rma\Test\Fixture\RmaShipping as RmaShippingFixture;
use Magento\Sales\Test\Fixture\Invoice as InvoiceFixture;
use Magento\Sales\Test\Fixture\Shipment as ShipmentFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for Adding return comment
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddReturnCommentTest extends GraphQlAbstract
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
     * @var Uid
     */
    private $idEncoder;

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
        $this->idEncoder = $this->objectManager->get(Uid::class);
    }

    /**
     * Test add comment to return with unauthorized customer
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testUnauthorized()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');

        $customerEmail = 'customer_uk_address@test.com';
        $rma = $this->getCustomerReturn($customerEmail);
        $rmaUid = $this->idEncoder->encode((string)$rma->getEntityId());

        $mutation = <<<MUTATION
mutation {
  addReturnComment(
    input: {
      return_uid: "{$rmaUid}",
      comment_text: "Additional return comment"
    }
  ) {
    return {
      uid
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
     * Test add comment to return when RMA is disabled
     */
    #[
        Config("sales/magento_rma/enabled", 0),
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
                'items' => [['product_id' => '$product.id$']]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
        DataFixture(
            RmaItemFixture::class,
            [
                'sku' => '$product.sku$',
                'name' => '$product.name$',
                'rma_entity_id' => '$rma.id$',
                'id' => '$rma.id$'
            ],
            'rma_item'
        ),
        DataFixture(
            RmaShippingFixture::class,
            [
                'rma_entity_id' => '$rma.id$',
            ],
            'rma_shipping'
        ),
    ]
    public function testRmaDisabled()
    {
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('RMA is disabled.');
        $customerEmail = $customer->getEmail();

        $rma = $this->getCustomerReturn($customerEmail);
        $rmaUid = $this->idEncoder->encode((string)$rma->getEntityId());

        $mutation = <<<MUTATION
mutation {
  addReturnComment(
    input: {
      return_uid: "{$rmaUid}",
      comment_text: "Additional return comment"
    }
  ) {
    return {
      uid
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
     * Test add comment to return
     */
    #[
        Config("sales/magento_rma/enabled", 1),
        Config("sales/magento_rma/use_store_address", 1),
        Config("shipping/origin/name", "test"),
        Config("shipping/origin/phone", +380003434343),
        Config("shipping/origin/street_line1", "street"),
        Config("shipping/origin/street_line2", "1"),
        Config("shipping/origin/city", "Montgomery"),
        Config("shipping/origin/region_id", 1),
        Config("shipping/origin/postcode", 12345),
        Config("shipping/origin/country_id", "US"),
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
                'items' => [['product_id' => '$product.id$']]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testAddComment()
    {
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $customerEmail = $customer->getEmail();
        $customerName = $customer->getName();
        $rma = $this->getCustomerReturn($customerEmail);
        $rmaUid = $this->idEncoder->encode((string)$rma->getEntityId());

        $mutation = <<<MUTATION
mutation {
  addReturnComment(
    input: {
      return_uid: "{$rmaUid}",
      comment_text: "Additional return comment"
    }
  ) {
    return {
      uid
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

        self::assertEquals($rmaUid, $response['addReturnComment']['return']['uid']);
        $this->assertRmaComments($response['addReturnComment']['return']['comments'], $customerName);
    }

    /**
     * Test add comment to return with wrong RMA uid
     */
    #[
        Config("sales/magento_rma/enabled", 1),
        Config("sales/magento_rma/use_store_address", 1),
        Config("shipping/origin/name", "test"),
        Config("shipping/origin/phone", +380003434343),
        Config("shipping/origin/street_line1", "street"),
        Config("shipping/origin/street_line2", "1"),
        Config("shipping/origin/city", "Montgomery"),
        Config("shipping/origin/region_id", 1),
        Config("shipping/origin/postcode", 12345),
        Config("shipping/origin/country_id", "US"),
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
                'items' => [['product_id' => '$product.id$']]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testWithWrongId()
    {
        $customerEmail = DataFixtureStorageManager::getStorage()->get('customer')->getEmail();
        $rma = $this->getCustomerReturn($customerEmail);
        $rmaId = $rma->getEntityId() + 10;
        $rmaUid = $this->idEncoder->encode((string)$rmaId);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You selected the wrong RMA.');

        $mutation = <<<MUTATION
mutation {
  addReturnComment(
    input: {
      return_uid: "{$rmaUid}",
      comment_text: "Additional return comment"
    }
  ) {
    return {
      uid
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
     * Test add comment to return with not encoded RMA uid
     */
    #[
        Config("sales/magento_rma/enabled", 1),
        Config("sales/magento_rma/use_store_address", 1),
        Config("shipping/origin/name", "test"),
        Config("shipping/origin/phone", +380003434343),
        Config("shipping/origin/street_line1", "street"),
        Config("shipping/origin/street_line2", "1"),
        Config("shipping/origin/city", "Montgomery"),
        Config("shipping/origin/region_id", 1),
        Config("shipping/origin/postcode", 12345),
        Config("shipping/origin/country_id", "US"),
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
                'items' => [['product_id' => '$product.id$']]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testWithNotEncodedId()
    {
        $customerEmail = DataFixtureStorageManager::getStorage()->get('customer')->getEmail();
        $rma = $this->getCustomerReturn($customerEmail);
        $rmaId = $rma->getEntityId();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Value of uid \"{$rmaId}\" is incorrect");

        $mutation = <<<MUTATION
mutation {
  addReturnComment(
    input: {
      return_uid: "{$rmaId}",
      comment_text: "Additional return comment"
    }
  ) {
    return {
      uid
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
     * Get customer return
     *
     * @param string $customerEmail
     * @return RmaInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCustomerReturn(string $customerEmail): RmaInterface
    {
        $customer = $this->customerRepository->get($customerEmail);
        $this->searchCriteriaBuilder->addFilter(Rma::CUSTOMER_ID, $customer->getId());
        $searchResults = $this->rmaRepository->getList($this->searchCriteriaBuilder->create());

        return $searchResults->getFirstItem();
    }

    /**
     * Assert RMA comments
     *
     * @param array $actualComments
     */
    private function assertRmaComments(array $actualComments, string $customerName): void
    {
        $expectedComments = [
            [
                'created_at' => date('Y-m-d H:i:s'),
                'author_name' => $customerName,
                'text' => 'Additional return comment'
            ]
        ];

        foreach ($expectedComments as $key => $expectedComment) {
            self::assertEqualsWithDelta(
                strtotime($expectedComment['created_at']),
                strtotime($actualComments[$key]['created_at']),
                10,
            );
            self::assertEquals($expectedComment['author_name'], $actualComments[$key]['author_name']);
            self::assertEquals($expectedComment['text'], $actualComments[$key]['text']);
        }
    }
}
