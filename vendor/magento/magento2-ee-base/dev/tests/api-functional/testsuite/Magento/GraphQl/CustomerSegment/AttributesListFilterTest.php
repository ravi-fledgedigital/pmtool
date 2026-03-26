<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GraphQl\CustomerSegment;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\Customer\Test\Fixture\CustomerAttribute;

/**
 * Test EAV attributes filter retrieval for entity type customer via GraphQL API
 */
#[
    DataFixture(
        CustomerAttribute::class,
        [
            'entity_type_id' => CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
            'frontend_input' => 'boolean',
            'source_model' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
            'is_used_for_customer_segment' => 1
        ],
        'customer_attribute_0'
    ),
    DataFixture(
        CustomerAttribute::class,
        [
            'entity_type_id' => CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
            'frontend_input' => 'boolean',
            'source_model' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean'
        ],
        'customer_attribute_1'
    ),
    DataFixture(
        CustomerAttribute::class,
        [
            'entity_type_id' => CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
            'frontend_input' => 'boolean',
            'source_model' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
            'is_used_for_customer_segment' => 1
        ],
        'customer_attribute_2'
    )
]
class AttributesListFilterTest extends GraphQlAbstract
{
    /**
     * @var AttributeInterface|null
     */
    private $customerAttribute0;

    /**
     * @var AttributeInterface|null
     */
    private $customerAttribute2;

    /**
     * @inheridoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->customerAttribute0 = DataFixtureStorageManager::getStorage()->get('customer_attribute_0');
        DataFixtureStorageManager::getStorage()->get('customer_attribute_1');
        $this->customerAttribute2 = DataFixtureStorageManager::getStorage()->get('customer_attribute_2');
        $this->customerAttribute2->setIsVisible(false)->save();
    }

    public function testAttributesListFilterForCustomerEntityType(): void
    {
        $queryResult = $this->graphQlQuery(<<<QRY
        {
            attributesList(entityType: CUSTOMER, filters: {is_used_for_customer_segment: true}) {
                items {
                    code
                }
                errors {
                    type
                    message
                }
            }
        }
QRY);
        $this->assertArrayHasKey('items', $queryResult['attributesList'], 'Query result does not contain items');
        $this->assertEquals(
            [
                'attributesList' => [
                    'items' => [
                        0 => [
                            'code' => $this->customerAttribute0->getAttributeCode()
                        ]
                    ],
                    'errors' => []
                ]
            ],
            $queryResult
        );
    }

    public function testAttributesListOneFilterNotApply(): void
    {
        $queryResult = $this->graphQlQuery(<<<QRY
        {
            attributesList(entityType: CUSTOMER, filters: {is_filterable: true, is_used_for_customer_segment: true}) {
                items {
                    code
                }
                errors {
                    type
                    message
                }
            }
        }
QRY);
        $this->assertArrayHasKey('items', $queryResult['attributesList'], 'Query result does not contain items');
        $this->assertEquals(
            [
                'attributesList' => [
                    'items' => [
                        0 => [
                            'code' => $this->customerAttribute0->getAttributeCode()
                        ]
                    ],
                    'errors' => [
                        0 => [
                            'type' => 'FILTER_NOT_FOUND',
                            'message' => 'Cannot filter by "is_filterable" as that field does not belong to "customer".'
                        ]
                    ]
                ]
            ],
            $queryResult
        );
    }
}
