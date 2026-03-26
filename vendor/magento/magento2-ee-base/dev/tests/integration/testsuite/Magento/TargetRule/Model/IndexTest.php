<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TargetRule\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Test\Fixture\MultiselectAttribute as MultiselectAttributeFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Store\Model\Store;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes;
use Magento\TargetRule\Model\ResourceModel\Rule as RuleResource;
use Magento\TargetRule\Model\Rule\Condition\Product\Attributes as RuleAttributes;
use Magento\TargetRule\Test\Fixture\Rule as RuleFixture;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test for Magento\TargetRule\Model\Index
 */
class IndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var RuleResource
     */
    private $resourceModel;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        $this->resourceModel = $this->objectManager->get(RuleResource::class);
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    /**
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/TargetRule/_files/products_with_attributes.php
     * @dataProvider rulesDataProvider
     *
     * @param int $ruleType
     * @param string $actionAttribute
     * @param string $valueType
     * @param string $operator
     * @param string $attributeValue
     * @param array $productsSku
     *
     * @return void
     */
    public function testGetProductIds(
        int $ruleType,
        string $actionAttribute,
        string $valueType,
        string $operator,
        string $attributeValue,
        array $productsSku
    ): void {
        $product = $this->productRepository->get('simple1');

        $conditions = $this->createConditions('category_ids', '==', 33);
        $actions = $this->createActions($actionAttribute, $valueType, $operator, $attributeValue);
        $model = $this->createRuleModel($ruleType, $conditions, $actions);
        /** @var Index $index */
        $index = $this->objectManager->create(Index::class)
            ->setType($ruleType)
            ->setProduct($product);
        $productIds = array_map(
            'intval',
            array_keys($index->getProductIds())
        );
        sort($productIds);
        $this->resourceModel->delete($model);

        $expectedProductIds = [];
        foreach ($productsSku as $sku) {
            $expectedProductIds[] = (int) $this->productRepository->get($sku)->getId();
        }
        sort($expectedProductIds);
        $this->assertEquals($expectedProductIds, $productIds);
    }

    /**
     * @return array
     */
    public function rulesDataProvider(): array
    {
        return [
            'cross sells rule by the same global attribute' => [
                Rule::CROSS_SELLS,
                'global_attribute',
                Attributes::VALUE_TYPE_SAME_AS,
                '==',
                '',
                ['simple2', 'simple3', 'simple4'],
            ],
            'related rule by the same category id' => [
                Rule::RELATED_PRODUCTS,
                'category_ids',
                Attributes::VALUE_TYPE_SAME_AS,
                '==',
                '',
                ['simple3'],
            ],
            'up sells rule by child of category ids' => [
                Rule::UP_SELLS,
                'category_ids',
                Attributes::VALUE_TYPE_CHILD_OF,
                '==',
                '',
                ['child_simple'],
            ],
            'cross sells rule by constant category ids' => [
                Rule::CROSS_SELLS,
                'category_ids',
                Attributes::VALUE_TYPE_CONSTANT,
                '==',
                '44',
                ['simple2', 'simple4'],
            ],
            'up sells rule by the same static attribute' => [
                Rule::UP_SELLS,
                'type_id',
                Attributes::VALUE_TYPE_SAME_AS,
                '==',
                '',
                ['simple2', 'simple3', 'simple4', 'child_simple'],
            ],
            'related rule by constant promo attribute' => [
                Rule::RELATED_PRODUCTS,
                'promo_attribute',
                Attributes::VALUE_TYPE_CONSTANT,
                '==',
                'RELATED_PRODUCT',
                ['simple2', 'simple3', 'simple4'],
            ],
            'related rule by attribute where value is equal to multiple values' => [
                Rule::RELATED_PRODUCTS,
                'promo_attribute',
                Attributes::VALUE_TYPE_CONSTANT,
                '==',
                'RELATED_PRODUCT,ANOTHER_PRODUCT',
                [],
            ],
            'related rule by scoped attribute where value is one of' => [
                Rule::RELATED_PRODUCTS,
                'promo_attribute',
                Attributes::VALUE_TYPE_CONSTANT,
                '()',
                'RELATED_PRODUCT,ANOTHER_PRODUCT',
                ['simple2', 'simple3', 'simple4', 'child_simple'],
            ],
            'related rule by global attribute where value is one of' => [
                Rule::RELATED_PRODUCTS,
                'global_attribute',
                Attributes::VALUE_TYPE_CONSTANT,
                '()',
                '666,777',
                ['simple2', 'simple3', 'simple4', 'child_simple'],
            ],
            'related rule by static attribute where value is one of' => [
                Rule::RELATED_PRODUCTS,
                'sku',
                Attributes::VALUE_TYPE_CONSTANT,
                '()',
                'simple2,child_simple',
                ['simple2', 'child_simple'],
            ],
        ];
    }

    #[
        DbIsolation(false),
        DataFixture(
            MultiselectAttributeFixture::class,
            [
                'attribute_code' => 'product_multiselect_attribute',
                'options' => ['option_1', 'option_2', 'option_3', 'option_4', 'option_5']
            ],
            'attr'
        ),
        DataFixture(ProductFixture::class, as: 'product1'),
        DataFixture(ProductFixture::class, as: 'product2'),
        DataFixture(ProductFixture::class, as: 'product3'),
        DataFixture(ProductFixture::class, as: 'product4'),
        DataFixture(ProductFixture::class, as: 'product5'),
        DataFixture(ProductFixture::class, as: 'product6'),
        DataFixture(
            RuleFixture::class,
            [
                'actions' => [
                    [
                        'attribute' => '$attr.attribute_code$',
                        'value' => ['$attr.option_1$','$attr.option_4$'],
                        'operator' => '()'
                    ]
                ]
            ],
            'rule'
        ),
    ]
    public function testConditionWithMultiselectAndConstant(): void
    {
        $this->assertMatchingProducts(
            'product6',
            ['product1', 'product3', 'product5'],
            [
                'product1' => ['option_1'],
                'product2' => ['option_2'],
                'product3' => ['option_1', 'option_2'],
                'product5' => ['option_4'],
            ],
        );
    }

    #[
        DbIsolation(false),
        DataFixture(
            MultiselectAttributeFixture::class,
            [
                'attribute_code' => 'product_multiselect_attribute',
                'options' => ['option_1', 'option_2', 'option_3', 'option_4', 'option_5']
            ],
            'attr'
        ),
        DataFixture(ProductFixture::class, as: 'product1'),
        DataFixture(ProductFixture::class, as: 'product2'),
        DataFixture(ProductFixture::class, as: 'product3'),
        DataFixture(ProductFixture::class, as: 'product4'),
        DataFixture(ProductFixture::class, as: 'product5'),
        DataFixture(ProductFixture::class, as: 'product6'),
        DataFixture(
            RuleFixture::class,
            [
                'actions' => [
                    [
                        'attribute' => '$attr.attribute_code$',
                        'operator' => '()',
                        'value_type' => Attributes::VALUE_TYPE_SAME_AS
                    ]
                ]
            ],
            'rule'
        ),
    ]
    public function testConditionWithMultiselectAndSameAs(): void
    {
        $this->assertMatchingProducts(
            'product3',
            ['product1', 'product2'],
            [
                'product1' => ['option_1'],
                'product2' => ['option_2'],
                'product3' => ['option_1', 'option_2'],
                'product5' => ['option_4'],
            ],
        );
    }

    /**
     * @param string $targetProduct
     * @param array $expectedProducts
     * @param array $productsConfiguration
     * @return void
     */
    private function assertMatchingProducts(
        string $targetProduct,
        array $expectedProducts,
        array $productsConfiguration
    ): void {
        /** @var Index $index */
        $index = $this->objectManager->create(Index::class)
            ->setType(Rule::RELATED_PRODUCTS);

        $multiselect = $this->fixtures->get('attr');
        $attributeCode = $multiselect->getAttributeCode();
        // set multiselect attribute
        foreach ($productsConfiguration as $fixture => $value) {
            $id = (int) $this->fixtures->get($fixture)->getId();
            $product = $this->productRepository->getById($id, true, Store::DEFAULT_STORE_ID, true);
            $product->setData($attributeCode, implode(',', array_map([$multiselect, 'getData'], $value)));
            $this->productRepository->save($product);
        }

        $targetProductId = (int) $this->fixtures->get($targetProduct)->getId();
        $product = $this->productRepository->getById($targetProductId, true, Store::DEFAULT_STORE_ID, true);
        $index->setProduct($product);

        $expectedProductIds = [];
        foreach ($expectedProducts as $fixture) {
            $expectedProductIds[] = (int) $this->fixtures->get($fixture)->getId();
        }

        $this->assertEqualsCanonicalizing($expectedProductIds, array_keys($index->getProductIds()));
    }

    /**
     * @dataProvider stockStatusRulesDataProvider
     * @param string $conditionsValue
     * @param string $actionValueType
     * @param string $actionOperator
     * @param string $actionValue
     * @param string $testSku
     * @param array $expectedSkus
     * @return void
     */
    #[
        DbIsolation(false),
        ConfigFixture('cataloginventory/options/show_out_of_stock', 1, 'store', 'default'),
        DataFixture(ProductFixture::class, ['sku' => 'simple_is1']),
        DataFixture(ProductFixture::class, ['sku' => 'simple_is2']),
        DataFixture(ProductFixture::class, ['sku' => 'simple_oos1', 'stock_item' => ['is_in_stock' => 0]]),
        DataFixture(ProductFixture::class, ['sku' => 'simple_oos2', 'stock_item' => ['is_in_stock' => 0]]),
    ]
    public function testGetProductIdsByStockStatus(
        string $conditionsValue,
        string $actionValueType,
        string $actionOperator,
        string $actionValue,
        string $testSku,
        array $expectedSkus
    ): void {
        $ruleType = Rule::RELATED_PRODUCTS;
        $product = $this->productRepository->get($testSku);

        $conditions = $this->createConditions('quantity_and_stock_status', '==', $conditionsValue);
        $actions = $this->createActions(
            'quantity_and_stock_status',
            $actionValueType,
            $actionOperator,
            $actionValue
        );
        $model = $this->createRuleModel($ruleType, $conditions, $actions);
        $index = $this->objectManager->create(Index::class)
            ->setType($ruleType)
            ->setProduct($product);
        $productIds = $index->getProductIds();
        $this->resourceModel->delete($model);

        $productSkus = [];
        foreach (array_keys($productIds) as $productId) {
            $productSkus[] = $this->productRepository->getById($productId)->getSku();
        }
        sort($productSkus);
        $this->assertEquals($expectedSkus, $productSkus);
    }

    public function stockStatusRulesDataProvider(): array
    {
        return [
            [
                '1',
                Attributes::VALUE_TYPE_CONSTANT,
                '==',
                '1',
                'simple_is1',
                ['simple_is2'],
            ],
            [
                '0',
                Attributes::VALUE_TYPE_CONSTANT,
                '==',
                '0',
                'simple_oos1',
                ['simple_oos2'],
            ],
            [
                '1',
                Attributes::VALUE_TYPE_CONSTANT,
                '==',
                '0',
                'simple_is2',
                ['simple_oos1', 'simple_oos2'],
            ],
            [
                '0',
                Attributes::VALUE_TYPE_CONSTANT,
                '==',
                '1',
                'simple_oos1',
                ['simple_is1', 'simple_is2'],
            ],
            [
                '1',
                Attributes::VALUE_TYPE_CONSTANT,
                '==',
                '0',
                'simple_oos1',
                [],
            ],
            [
                '1',
                Attributes::VALUE_TYPE_SAME_AS,
                '==',
                '',
                'simple_is1',
                ['simple_is2'],
            ],
            [
                '0',
                Attributes::VALUE_TYPE_SAME_AS,
                '==',
                '',
                'simple_oos1',
                ['simple_oos2'],
            ],
            [
                '0',
                Attributes::VALUE_TYPE_SAME_AS,
                '==',
                '',
                'simple_is1',
                [],
            ],
        ];
    }

    /**
     * @param string $attribute
     * @param string $operator
     * @param string|int $value
     * @return array
     */
    private function createConditions(string $attribute, string $operator, $value): array
    {
        $conditions = [
            'type' => \Magento\TargetRule\Model\Actions\Condition\Combine::class,
            'aggregator' => 'all',
            'value' => 1,
            'new_child' => '',
            'conditions' => [],
        ];
        $conditions['conditions'][1] = [
            'type' => RuleAttributes::class,
            'attribute' => $attribute,
            'operator' => $operator,
            'value' => $value,
        ];

        return $conditions;
    }

    /**
     * @param string $actionAttribute
     * @param string $valueType
     * @param string $operator
     * @param string $attributeValue
     * @return array
     */
    private function createActions(
        string $actionAttribute,
        string $valueType,
        string $operator,
        string $attributeValue
    ): array {
        $actions = [
            'type' => \Magento\TargetRule\Model\Actions\Condition\Combine::class,
            'aggregator' => 'all',
            'value' => 1,
            'new_child' => '',
            'actions' => [],
        ];
        $actions['actions'][1] = [
            'type' => Attributes::class,
            'attribute' => $actionAttribute,
            'operator' => $operator,
            'value_type' => $valueType,
            'value' => $attributeValue,
        ];

        return $actions;
    }

    /**
     * @param int $ruleType
     * @param array $conditions
     * @param array $actions
     * @return Rule
     */
    private function createRuleModel(int $ruleType, array $conditions, array $actions): Rule
    {
        /** @var Rule $model */
        $model = $this->objectManager->create(Rule::class);
        $model->setName('Test rule');
        $model->setSortOrder(0);
        $model->setIsActive(1);
        $model->setApplyTo($ruleType);
        $model->getConditions()->setConditions([])->loadArray($conditions);
        $model->getActions()->setActions([])->loadArray($actions, 'actions');
        $this->resourceModel->save($model);

        return $model;
    }
}
