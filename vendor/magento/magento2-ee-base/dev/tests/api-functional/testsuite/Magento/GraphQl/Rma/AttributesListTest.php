<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GraphQl\Rma;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Eav\Test\Fixture\Attribute;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Rma\Api\RmaAttributesManagementInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;

/**
 * Test EAV attributes metadata retrieval for entity type via GraphQL API
 */
#[
    DataFixture(
        Attribute::class,
        [
            'entity_type_id' => CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
            'frontend_input' => 'boolean',
            'source_model' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean'
        ],
        'customerAttribute'
    ),
    DataFixture(
        Attribute::class,
        [
            'entity_type_id' => CategorySetup::CATALOG_PRODUCT_ENTITY_TYPE_ID,
            'frontend_input' => 'boolean',
            'source_model' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean'
        ],
        'catalogAttribute'
    ),
    DataFixture(
        Attribute::class,
        [
            'entity_type_id' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
            'frontend_input' => 'multiline',
            'default_value' => 'this is line one
this is line two',
        ],
        'rmaItemAttribute'
    )
]
class AttributesListTest extends GraphQlAbstract
{
    private const ATTRIBUTE_NOT_FOUND_ERROR = "Attribute was not found in query result";

    public function testAttributesListForRmaItemEntityType(): void
    {
        $queryResult = $this->graphQlQuery(<<<QRY
        {
            attributesList(entityType: RMA_ITEM) {
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
        $this->assertGreaterThanOrEqual(1, count($queryResult['attributesList']['items']));

        /** @var AttributeInterface $rmaItemAttribute */
        $rmaItemAttribute = DataFixtureStorageManager::getStorage()->get('rmaItemAttribute');

        /** @var AttributeInterface $catalogAttribute */
        $catalogAttribute = DataFixtureStorageManager::getStorage()->get('catalogAttribute');

        /** @var AttributeInterface $customerAttribute */
        $customerAttribute = DataFixtureStorageManager::getStorage()->get('customerAttribute');

        $this->assertEquals(
            $rmaItemAttribute->getAttributeCode(),
            $this->getAttributeByCode(
                $queryResult['attributesList']['items'],
                $rmaItemAttribute->getAttributeCode()
            )['code'],
            self::ATTRIBUTE_NOT_FOUND_ERROR
        );
        $this->assertEquals(
            [],
            $this->getAttributeByCode(
                $queryResult['attributesList']['items'],
                $catalogAttribute->getAttributeCode()
            )
        );
        $this->assertEquals(
            [],
            $this->getAttributeByCode(
                $queryResult['attributesList']['items'],
                $customerAttribute->getAttributeCode()
            )
        );
    }

    /**
     * Finds attribute in query result
     *
     * @param array $items
     * @param string $attribute_code
     * @return array
     */
    private function getAttributeByCode(array $items, string $attribute_code): array
    {
        $attribute = array_filter($items, function ($item) use ($attribute_code) {
            return $item['code'] == $attribute_code;
        });
        return $attribute[array_key_first($attribute)] ?? [];
    }
}
