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
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Eav\Test\Fixture\AttributeOption;
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
 * Test for set billing address on cart mutation
 */
class SetBillingAddressOnCartWithCustomMultiselectAttributeTest extends GraphQlAbstract
{
    private const QUERY = <<<QUERY
mutation {
  setBillingAddressOnCart(
    input: {
      cart_id: "%s"
      billing_address: {
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
            custom_attributes: {
                attribute_code: "%s",
                selected_options: [
                    {
                        value: "%s"
                    },
                    {
                        value: "%s"
                    }
                ]
            }
          }
        }
    }
  ) {
    cart {
      billing_address {
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
        custom_attributes {
            code
            ... on AttributeSelectedOptions {
                selected_options {
                    label
                    value
                }
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
                'frontend_input' => 'multiselect',
                'source_model' => Table::class
            ],
            'custom_attribute',
        ),
        DataFixture(
            AttributeOption::class,
            [
                'entity_type' => AddressMetadataInterface::ATTRIBUTE_SET_ID_ADDRESS,
                'attribute_code' => '$custom_attribute.attribute_code$',
                'sort_order' => 20
            ],
            'option1'
        ),
        DataFixture(
            AttributeOption::class,
            [
                'entity_type' => AddressMetadataInterface::ATTRIBUTE_SET_ID_ADDRESS,
                'attribute_code' => '$custom_attribute.attribute_code$',
                'sort_order' => 10,
                'is_default' => true
            ],
            'option2'
        ),
        DataFixture(
            AttributeOption::class,
            [
                'entity_type' => AddressMetadataInterface::ATTRIBUTE_SET_ID_ADDRESS,
                'attribute_code' => '$custom_attribute.attribute_code$',
                'sort_order' => 30,
                'is_default' => true
            ],
            'option3'
        ),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(QuoteIdMask::class, ['cart_id' => '$quote.id$'], 'quoteIdMask')
    ]
    public function testSetBillingMethod()
    {
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        /** @var Attribute $attribute */
        $attribute = DataFixtureStorageManager::getStorage()->get('custom_attribute');
        $attributeCode = $attribute->getAttributeCode();

        /** @var AttributeOptionInterface $option1 */
        $option1 = DataFixtureStorageManager::getStorage()->get('option1');
        $value1 = $option1->getValue();

        /** @var AttributeOptionInterface $option2 */
        $option2 = DataFixtureStorageManager::getStorage()->get('option2');
        $value2 = $option2->getValue();
        $objectManager = Bootstrap::getObjectManager();

        $query = sprintf(
            self::QUERY,
            $maskedQuoteId,
            $attributeCode,
            $value1,
            $value2
        );
        $response = $this->graphQlMutation($query, [], '', $this->getHeaders());

        $this->assertEquals(
            $this->getExpectedResult($attributeCode, [$option2, $option1]),
            $response
        );

        /** @var Quote $resultQuote */
        $resultQuote = $objectManager->get(CartRepositoryInterface::class)->get(
            DataFixtureStorageManager::getStorage()->get('quote')->getId()
        );
        $resultCustomAttributes = $resultQuote->getBillingAddress()->getCustomAttributes();
        $this->assertEquals(
            [
                new AttributeValue(
                    [
                        'attribute_code' => $attributeCode,
                        'value' => $value1 . ',' . $value2
                    ]
                )
            ],
            $resultCustomAttributes
        );
    }

    /**
     * Expected query response
     *
     * @param string $attributeCode
     * @param array $options
     * @return \array[][][]
     */
    private function getExpectedResult(string $attributeCode, array $options): array
    {
        return [
            'setBillingAddressOnCart' => [
                'cart' => [
                    'billing_address' => [
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
                        '__typename' => 'BillingCartAddress',
                        'custom_attributes' => [
                            [
                                'code' => $attributeCode,
                                'selected_options' => array_map(
                                    function (AttributeOptionInterface $option) {
                                        return [
                                            'value' => $option->getValue(),
                                            'label' => $option->getLabel()
                                        ];
                                    },
                                    $options
                                )
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
