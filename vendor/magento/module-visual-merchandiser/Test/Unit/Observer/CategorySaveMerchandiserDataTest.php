<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Test\Unit\Observer;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\VisualMerchandiser\Model\Position\Cache;
use Magento\VisualMerchandiser\Model\Rules;
use Magento\VisualMerchandiser\Observer\CategorySaveMerchandiserData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Test for \Magento\VisualMerchandiser\Observer\CategorySaveMerchandiserData.
 */
class CategorySaveMerchandiserDataTest extends TestCase
{
    /**
     * @var CategorySaveMerchandiserData
     */
    private $categorySaveMerchandiserDataObserver;

    /**
     * @var Cache|MockObject
     */
    private $cacheMock;

    /**
     * @var Rules|MockObject
     */
    private $rulesMock;

    /**
     * @var CategoryRepositoryInterface|MockObject
     */
    private $categoryRepositoryMock;

    /**
     * @var Http|MockObject
     */
    private $requestMock;

    /**
     * @var string
     */
    private static $cacheKey;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->onlyMethods(['getPostValue'])
            ->disableOriginalConstructor()
            ->getMock();
        self::$cacheKey = '5a8fb8ef75270';
        $this->cacheMock = $this->getMockBuilder(Cache::class)
            ->onlyMethods(['getPositions'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rulesMock = $this->getMockBuilder(Rules::class)
            ->onlyMethods(['loadByCategory'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryRepositoryMock = $this->getMockBuilder(CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManagerHelper = new ObjectManager($this);

        $this->categorySaveMerchandiserDataObserver = $objectManagerHelper->getObject(
            CategorySaveMerchandiserData::class,
            [
                '_cache' => $this->cacheMock,
                '_rules' => $this->rulesMock,
                'categoryRepository' => $this->categoryRepositoryMock,
            ]
        );
    }

    /**
     * Test for new category.
     *
     * @return void
     */
    public function testNewCategoryExecute(): void
    {
        $this->requestMock->expects($this->atLeastOnce())
            ->method('getPostValue')
            ->willReturnCallback(function ($arg1) {
                if ($arg1 == Cache::POSITION_CACHE_KEY) {
                    return self::$cacheKey;
                } elseif (empty($arg1)) {
                    return [];
                }
            });

        $this->cacheMock->expects($this->atLeastOnce())->method('getPositions')->willReturn(false);
        $categoryMock = $this->getCategoryMock([], null);
        $eventMock = $this->getEventMock($categoryMock);
        $observerMock = $this->getObserverMock($eventMock);

        $this->categorySaveMerchandiserDataObserver->execute($observerMock);
    }

    /**
     * Test for category with matching products by rule.
     *
     * @dataProvider smartCategoryDataProvider
     * @param array $postData
     * @param string $methodsCall
     * @param array $ruleOrigData
     * @return void
     */
    public function testSmartCategoryExecute(array $postData, string $methodsCall, array $ruleOrigData): void
    {
        $origData = [
            'entity_id' => $postData['entity_id'],
            'name' => 'TEST',
        ];
        $ruleId = $ruleOrigData === [] ? null : $ruleOrigData['rule_id'];
        $this->requestMock->expects($this->atLeastOnce())
            ->method('getPostValue')
            ->willReturnMap(
                [
                    [Cache::POSITION_CACHE_KEY, null, self::$cacheKey],
                    [null, null, $postData],
                ]
            );
        $this->cacheMock->expects($this->once())->method('getPositions')->willReturn(false);

        $categoryMock = $this->getCategoryMock($origData, $postData['entity_id']);
        $eventMock = $this->getEventMock($categoryMock);
        $observerMock = $this->getObserverMock($eventMock);

        $ruleMock = $this->getMockBuilder(Rules::class)
            ->onlyMethods(['setData', 'save', 'getId', 'getOrigData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->rulesMock->expects($this->$methodsCall())
            ->method('loadByCategory')
            ->with($categoryMock)
            ->willReturn($ruleMock);
        $ruleMock->expects($this->$methodsCall())->method('getId')->willReturn($ruleId);
        if ($ruleId !== null || !empty($postData['smart_category_rules'])) {
            $ruleMock->expects($this->once())->method('getOrigData')->willReturn($ruleOrigData);
            if ($ruleOrigData) {
                $categoryMock->expects($this->exactly(2))->method('setOrigData')
                    ->willReturnCallback(function ($arg1, $arg2) use ($categoryMock, $ruleOrigData) {
                        if ($arg1 == 'is_smart_category' && $arg2 == $ruleOrigData['is_active']) {
                            return null;
                        } elseif ($arg1 == 'smart_category_rules' && $arg2 == $ruleOrigData['conditions_serialized']) {
                            return null;
                        }
                    });
            }
            $ruleMock->expects($this->$methodsCall())->method('setData');
            $ruleMock->expects($this->$methodsCall())->method('save');
        }

        $this->categorySaveMerchandiserDataObserver->execute($observerMock);
    }

    /**
     * @return array
     */
    public static function smartCategoryDataProvider(): array
    {
        $entityId = 3;
        $json = new Json();
        return [
            [
                [
                    Cache::POSITION_CACHE_KEY => self::$cacheKey,
                    'entity_id' => $entityId,
                    'is_smart_category' => true,
                    'smart_category_rules' => '[{"attribute":"price","operator":"lt","value":"100","logic":"OR"}]',
                ],
                'atLeastOnce',
                [
                    'rule_id' => "1",
                    'category_id' => $entityId,
                    'store_id' => "0",
                    'is_active' => "0",
                    'conditions_serialized' => $json->serialize([
                        [
                            "attribute" => "color",
                            "operator" => "eq",
                            "value" => "Orange",
                            "logic" => "OR"
                        ],
                        [
                            "attribute" => "color",
                            "operator" => "eq",
                            "value" => "Blue",
                            "logic" => "OR"
                        ],
                        [
                            "attribute" => "quantity_and_stock_status",
                            "operator" => "neq",
                            "value" => "10",
                            "logic" => "OR"
                        ]
                    ])
                ]
            ],
            [
                [
                    Cache::POSITION_CACHE_KEY => self::$cacheKey,
                    'entity_id' => $entityId,
                ],
                'never',
                []
            ],
        ];
    }

    /**
     * @param array $origData
     * @param int|null $categoryId
     * @return MockObject
     */
    private function getCategoryMock(array $origData, $categoryId): MockObject
    {
        $categoryMock = $this->getMockBuilder(Category::class)
            ->onlyMethods(['getId', 'getOrigData', 'setOrigData'])
            ->disableOriginalConstructor()
            ->getMock();

        $categoryMock->expects($this->any())
            ->method('getId')
            ->willReturn($categoryId);

        $categoryMock->expects($this->any())
            ->method('getOrigData')
            ->willReturn($origData);

        return $categoryMock;
    }

    /**
     * @param MockObject $categoryMock
     * @return MockObject
     */
    private function getEventMock(MockObject $categoryMock): MockObject
    {
        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getCategory', 'getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->atLeastOnce())
            ->method('getCategory')
            ->willReturn($categoryMock);
        $eventMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        return $eventMock;
    }

    /**
     * @param MockObject $eventMock
     * @return MockObject
     */
    private function getObserverMock(MockObject $eventMock): MockObject
    {
        $observerMock = $this->getMockBuilder(Observer::class)
            ->onlyMethods(['getEvent'])
            ->disableOriginalConstructor()
            ->getMock();

        $observerMock->expects($this->any())->method('getEvent')->willReturn($eventMock);

        return $observerMock;
    }
}
