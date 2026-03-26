<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Rma\Attribute;

use Magento\Customer\Api\Data\AttributeMetadataInterface;
use Magento\Customer\Test\Fixture\CustomerAttribute;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Eav\Test\Fixture\Attribute;
use Magento\Eav\Test\Fixture\AttributeOption;
use Magento\GraphQl\Customer\Attribute\FormatValidationRulesCommand;
use Magento\Rma\Api\RmaAttributesManagementInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test Return Item attribute metadata retrieval via GraphQL API
 */
class ReturnItemAttributeMetadataTest extends GraphQlAbstract
{
    private const QUERY = <<<QRY
{
  customAttributeMetadataV2(attributes: [{attribute_code: "%s", entity_type: "%s"}]) {
    items {
      code
      default_value
      entity_type
      frontend_input
      label
      is_required
      is_unique
      options {
        label
        value
      }
      ... on ReturnItemAttributeMetadata {
        input_filter
        multiline_count
        sort_order
        validate_rules {
          name
          value
        }
      }
    }
    errors {
      type
      message
    }
  }
}
QRY;

    #[
        DataFixture(
            CustomerAttribute::class,
            [
                'entity_type_id' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'frontend_input' => 'date',
                'default_value' => '2023-03-22 00:00:00',
                'input_filter' => 'DATE',
                'validate_rules' =>
                    '{"DATE_RANGE_MIN":"1679443200","DATE_RANGE_MAX":"1679875200","INPUT_VALIDATION":"DATE"}'
            ],
            'attribute'
        ),
    ]
    public function testAttributeWithValidationRules(): void
    {
        /** @var AttributeMetadataInterface $attribute */
        $attribute = DataFixtureStorageManager::getStorage()->get('attribute');

        $formattedValidationRules = Bootstrap::getObjectManager()->get(FormatValidationRulesCommand::class)->execute(
            $attribute->getValidationRules()
        );

        $result = $this->graphQlQuery(
            sprintf(
                self::QUERY,
                $attribute->getAttributeCode(),
                RmaAttributesManagementInterface::ENTITY_TYPE
            )
        );

        $this->assertEquals(
            [
                'customAttributeMetadataV2' => [
                    'items' => [
                        [
                            'code' => $attribute->getAttributeCode(),
                            'default_value' => $attribute->getDefaultValue(),
                            'entity_type' => 'RMA_ITEM',
                            'frontend_input' => 'DATE',
                            'input_filter' => $attribute->getInputFilter(),
                            'is_required' => false,
                            'is_unique' => false,
                            'label' => $attribute->getFrontendLabel(),
                            'multiline_count' => $attribute->getMultilineCount(),
                            'options' => [],
                            'sort_order' => $attribute->getSortOrder(),
                            'validate_rules' => $formattedValidationRules
                        ]
                    ],
                    'errors' => []
                ]
            ],
            $result
        );
    }

    #[
        DataFixture(
            Attribute::class,
            [
                'entity_type_id' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'frontend_input' => 'multiselect',
                'source_model' => Table::class
            ],
            'attribute'
        ),
        DataFixture(
            AttributeOption::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$attribute.attribute_code$',
                'sort_order' => 10
            ],
            'option1'
        ),
        DataFixture(
            AttributeOption::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$attribute.attribute_code$',
                'sort_order' => 20,
                'is_default' => true
            ],
            'option2'
        ),
        DataFixture(
            AttributeOption::class,
            [
                'entity_type' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'attribute_code' => '$attribute.attribute_code$',
                'sort_order' => 30,
                'is_default' => true
            ],
            'option3'
        )
    ]
    public function testAttributeWithOptions(): void
    {
        /** @var AttributeMetadataInterface $attribute */
        $attribute = DataFixtureStorageManager::getStorage()->get('attribute');

        /** @var AttributeOptionInterface $attribute */
        $option1 = DataFixtureStorageManager::getStorage()->get('option1');

        /** @var AttributeOptionInterface $attribute */
        $option2 = DataFixtureStorageManager::getStorage()->get('option2');

        /** @var AttributeOptionInterface $attribute */
        $option3 = DataFixtureStorageManager::getStorage()->get('option3');

        $result = $this->graphQlQuery(
            sprintf(
                self::QUERY,
                $attribute->getAttributeCode(),
                RmaAttributesManagementInterface::ENTITY_TYPE
            )
        );

        $this->assertEquals(
            [
                'customAttributeMetadataV2' => [
                    'items' => [
                        [
                            'code' => $attribute->getAttributeCode(),
                            'default_value' => $option3->getValue() . ',' . $option2->getValue(),
                            'entity_type' => 'RMA_ITEM',
                            'frontend_input' => 'MULTISELECT',
                            'input_filter' => 'NONE',
                            'is_required' => false,
                            'is_unique' => false,
                            'label' => $attribute->getFrontendLabel(),
                            'multiline_count' => $attribute->getMultilineCount(),
                            'options' => [
                                [
                                    'label' => $option1->getLabel(),
                                    'value' => $option1->getValue()
                                ],
                                [
                                    'label' => $option2->getLabel(),
                                    'value' => $option2->getValue()
                                ],
                                [
                                    'label' => $option3->getLabel(),
                                    'value' => $option3->getValue()
                                ]
                            ],
                            'sort_order' => $attribute->getSortOrder(),
                            'validate_rules' => []
                        ]
                    ],
                    'errors' => []
                ]
            ],
            $result
        );
    }

    #[
        DataFixture(
            CustomerAttribute::class,
            [
                'entity_type_id' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
                'frontend_input' => 'multiline',
                'default_value' => 'this is line one
this is line two',
                'input_filter' => 'STRIPTAGS',
                'multiline_count' => 2,
                'sort_order' => 3,
                'validate_rules' => '{"MIN_TEXT_LENGTH":"100","MAX_TEXT_LENGTH":"200","INPUT_VALIDATION":"EMAIL"}',
            ],
            'attribute'
        )
    ]
    public function testAttributeWithMultilineCount(): void
    {
        /** @var AttributeMetadataInterface $attribute */
        $attribute = DataFixtureStorageManager::getStorage()->get('attribute');

        $formattedValidationRules = Bootstrap::getObjectManager()->get(FormatValidationRulesCommand::class)->execute(
            $attribute->getValidationRules()
        );

        $result = $this->graphQlQuery(
            sprintf(
                self::QUERY,
                $attribute->getAttributeCode(),
                RmaAttributesManagementInterface::ENTITY_TYPE
            )
        );

        $this->assertEquals(
            [
                'customAttributeMetadataV2' => [
                    'items' => [
                        [
                            'code' => $attribute->getAttributeCode(),
                            'default_value' => $attribute->getDefaultValue(),
                            'entity_type' => 'RMA_ITEM',
                            'frontend_input' => 'MULTILINE',
                            'input_filter' => $attribute->getInputFilter(),
                            'is_required' => false,
                            'is_unique' => false,
                            'label' => $attribute->getFrontendLabel(),
                            'multiline_count' => $attribute->getMultilineCount(),
                            'options' => [],
                            'sort_order' => $attribute->getSortOrder(),
                            'validate_rules' => $formattedValidationRules
                        ]
                    ],
                    'errors' => []
                ]
            ],
            $result
        );
    }
}
