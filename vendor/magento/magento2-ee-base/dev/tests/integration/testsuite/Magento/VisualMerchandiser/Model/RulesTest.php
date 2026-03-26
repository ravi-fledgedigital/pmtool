<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\Area;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\VisualMerchandiser\Model\Category\Products;
use Magento\VisualMerchandiser\Model\Position\Cache;

#[
    AppArea(Area::AREA_ADMINHTML),
    DbIsolation(false),
    AppIsolation(true),
]
/**
 * Class RulesTest to verify category collection with applied conditions
 */
class RulesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Rules
     */
    private $rulesModel;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->rulesModel = $this->objectManager->create(Rules::class);
        $this->categoryRepository = $this->objectManager->get(CategoryRepositoryInterface::class);
    }

    /**
     * Test save positions with applied conditions
     *
     * @magentoDataFixture Magento/Store/_files/second_website_with_two_stores.php
     * @magentoDataFixture Magento/Catalog/_files/category.php
     * @magentoDataFixture Magento/VisualMerchandiser/Block/Adminhtml/Category/Merchandiser/_files/products_with_websites_and_stores.php
     */
    public function testSavePositions()
    {
        $positionCacheKey = 'position-cache-key';

        $categoryId = 333;
        $category = $this->categoryRepository->get($categoryId);

        $conditions = [
            [
                'attribute' => 'price',
                'operator' => 'lt',
                'value' => 100,
                'logic' => 'OR'
            ]
        ];

        /** @var $serializer Json */
        $serializer = $this->objectManager->get(Json::class);
        $serializedConditions = $serializer->serialize($conditions);

        $rule = $this->rulesModel->loadByCategory($category);
        $rule->setData([
            'category_id' => $categoryId,
            'is_active' => 1,
            'conditions_serialized' => $serializedConditions
        ]);
        $rule->save();
        $this->categoryRepository->save($category);

        $productsModel = $this->objectManager->get(Products::class);
        $productsModel->setCacheKey($positionCacheKey);
        $collection = $productsModel->getCollectionForGrid($categoryId);
        $productIds = [];
        foreach ($collection as $item) {
            $productIds[] = $item->getId();
        }
        shuffle($productIds);

        /** @var Cache $positionCache */
        $positionCache = $this->objectManager->get(Cache::class);
        $positionCache->saveData($positionCacheKey, array_flip($productIds));
        $collection = $productsModel->getCollectionForGrid($categoryId);
        $productsModel->savePositions($collection);
        $cachedPositions = $positionCache->getPositions($positionCacheKey);
        $this->assertEquals($productIds, array_keys($cachedPositions), 'Positions are not saved.');
    }

    #[
        DataFixture('Magento/Catalog/_files/categories.php'),
    ]
    /**
     * @dataProvider applyConditionsDataProvider
     * @param array $conditions
     * @param array $expectedSkus
     * @return void
     */
    public function testApplyConditions(array $conditions, array $expectedSkus): void
    {
        $category = $this->categoryRepository->get(6);
        $productCollection = Bootstrap::getObjectManager()->create(ProductCollection::class);
        $this->rulesModel->applyConditions($category, $productCollection, $conditions);
        $skus = $productCollection->getColumnValues('sku');
        self::assertSame($expectedSkus, $skus);
    }

    public function applyConditionsDataProvider(): array
    {
        return [
            [
                [
                    [
                        'attribute' => 'category_id',
                        'value' => '4,13',
                        'operator' => 'eq',
                        'logic' => 'AND',
                    ],
                ],
                ['simple', '12345', 'simple-4'],
            ],
            [
                [
                    [
                        'attribute' => 'category_ids',
                        'value' => '4,13',
                        'operator' => 'eq',
                        'logic' => 'AND',
                    ],
                ],
                ['simple', '12345', 'simple-4'],
            ],
            [
                [
                    [
                        'attribute' => 'category_id',
                        'value' => '4,13',
                        'operator' => 'neq',
                        'logic' => 'AND',
                    ],
                ],
                ['simple-3'],
            ],
            [
                [
                    [
                        'attribute' => 'category_ids',
                        'value' => '4,13',
                        'operator' => 'neq',
                        'logic' => 'AND',
                    ],
                ],
                ['simple-3'],
            ],
        ];
    }

    public static function tearDownAfterClass(): void
    {
        $cache = ObjectManager::getInstance()->get(CacheInterface::class);
        $cache->remove(Cache::CACHE_PREFIX . Cache::POSITION_CACHE_KEY);
    }
}
