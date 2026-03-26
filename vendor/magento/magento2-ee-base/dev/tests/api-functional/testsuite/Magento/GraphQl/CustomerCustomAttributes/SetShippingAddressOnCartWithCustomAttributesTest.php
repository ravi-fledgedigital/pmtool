<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CustomerCustomAttributes;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Attribute;
use Magento\Customer\Test\Fixture\Customer;
use Magento\Customer\Test\Fixture\CustomerAttribute;
use Magento\Framework\Api\AttributeValue;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\Quote\Test\Fixture\QuoteIdMask;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for set shipping addresses on cart mutation
 */
class SetShippingAddressOnCartWithCustomAttributesTest extends GraphQlAbstract
{
    private const QUERY = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "%s"
      shipping_addresses: [
        {
          address: {
            firstname: "test firstname"
            lastname: "test lastname"
            company: "test company"
            street: ["test street 1", "test street 2"]
            city: "test city"
            region: "AZ"
            region_id: 4
            postcode: "887766"
            country_code: "US"
            telephone: "88776655"
            custom_attributes: [
                {
                    attribute_code: "%s"
                    value: "the value"
                },
                {
                    attribute_code: "%s"
                    value: "another value"
                }
            ]
          }
          customer_notes: "Test note"
        }
      ]
    }
  ) {
    cart {
      shipping_addresses {
        firstname
        lastname
        company
        street
        city
        postcode
        telephone
        country {
          label
          code
        }
        __typename
        customer_notes
        custom_attributes {
            code
            ... on AttributeValue {
                value
            }
        }
      }
    }
  }
}
QUERY;

    #[
        DataFixture(
            CustomerAttribute::class,
            [
                'entity_type_id' => AddressMetadataInterface::ATTRIBUTE_SET_ID_ADDRESS,
                'attribute_set_id' => AddressMetadataInterface::ATTRIBUTE_SET_ID_ADDRESS,
                'attribute_group_id' => 1,
            ],
            'custom_attribute',
        ),
        DataFixture(
            CustomerAttribute::class,
            [
                'entity_type_id' => AddressMetadataInterface::ATTRIBUTE_SET_ID_ADDRESS,
                'attribute_set_id' => AddressMetadataInterface::ATTRIBUTE_SET_ID_ADDRESS,
                'attribute_group_id' => 1,
                'sort_order' => 1
            ],
            'custom_attribute_2',
        ),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(QuoteIdMask::class, ['cart_id' => '$quote.id$'], 'quoteIdMask')
    ]
    public function testSetShippingMethod()
    {
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        /** @var Attribute $attribute */
        $attribute = DataFixtureStorageManager::getStorage()->get('custom_attribute');
        $attributeCode = $attribute->getAttributeCode();

        $attribute2 =  DataFixtureStorageManager::getStorage()->get('custom_attribute_2');
        $attributeCode2 = $attribute2->getAttributeCode();

        $query = sprintf(self::QUERY, $maskedQuoteId, $attributeCode, $attributeCode2);
        $response = $this->graphQlMutation($query, [], '', $this->getHeaders());

        $objectManager = Bootstrap::getObjectManager();
        $this->assertEquals(
            $this->getExpectedResult($attributeCode, $attributeCode2),
            $response
        );

        /** @var Quote $resultQuote */
        $resultQuote = $objectManager->get(CartRepositoryInterface::class)->get(
            DataFixtureStorageManager::getStorage()->get('quote')->getId()
        );
        $resultCustomAttributes = $resultQuote->getShippingAddress()->getCustomAttributes();
        $this->assertEquals(
            [
                new AttributeValue(
                    [
                        'attribute_code' => $attributeCode2,
                        'value' => 'another value'
                    ]
                ),
                new AttributeValue(
                    [
                        'attribute_code' => $attributeCode,
                        'value' => 'the value'
                    ]
                )
            ],
            $resultCustomAttributes
        );
    }

    /**
     * Expected query result
     *
     * @param string $attributeCode
     * @return \array[][][][]
     */
    private function getExpectedResult(string $attributeCode, $attributeCode2): array
    {
        return [
            'setShippingAddressesOnCart' => [
                'cart' => [
                    'shipping_addresses' => [
                        [
                            'firstname' => 'test firstname',
                            'lastname' => 'test lastname',
                            'company' => 'test company',
                            'street' => ["test street 1", "test street 2"],
                            'city' => 'test city',
                            'postcode' => '887766',
                            'telephone' => '88776655',
                            'country' => [
                                'label' => 'US',
                                'code' => 'US'
                            ],
                            '__typename' => 'ShippingCartAddress',
                            'customer_notes' => 'Test note',
                            'custom_attributes' => [
                                [
                                    'code' => $attributeCode2,
                                    'value' => 'another value'
                                ],
                                [
                                    'code' => $attributeCode,
                                    'value' => 'the value'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get customer authentication header
     *
     * @param string $customerFixtureName
     * @return array
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function getHeaders(string $customerFixtureName = 'customer'): array
    {
        /** @var CustomerInterface $customer */
        $customer = DataFixtureStorageManager::getStorage()->get($customerFixtureName);
        return Bootstrap::getObjectManager()->get(GetCustomerAuthenticationHeader::class)
            ->execute($customer->getEmail());
    }
}
