<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Test\Unit\Model\Product;

use Magento\Catalog\Model\Category\Product\PositionResolver;
use Magento\Catalog\Model\ResourceModel\Product\Collection\ProductLimitation;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Store\Model\Store;
use Magento\VisualMerchandiser\Model\Position\Cache;
use Magento\VisualMerchandiser\Model\Product\DataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DataProviderTest extends TestCase
{
    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactory;

    /**
     * @var AbstractCollection|MockObject
     */
    private $collection;

    /**
     * @var Cache|MockObject
     */
    private $cache;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var PositionResolver|MockObject
     */
    private $positionResolver;

    /**
     * @var string
     */
    private $positionCacheKey;

    /**
     * @var DataProvider
     */
    private $model;

    /**
     * Set up instances and mock objects
     */
    protected function setUp(): void
    {
        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection = $this->getMockBuilder(AbstractCollection::class)
            ->addMethods(['addAttributeToSelect', 'joinField', 'setStoreId', 'getLimitationFilters'])
            ->onlyMethods(['getIterator', 'getSize', 'getAllIds', 'getItems'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->collection);
        $this->collection
            ->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturnSelf();
        $this->collection
            ->expects($this->any())
            ->method('joinField')
            ->willReturnSelf();
        $this->cache = $this->getMockBuilder(Cache::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->positionResolver = $this->getMockBuilder(PositionResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new DataProvider(
            'catalog',
            'entity_id',
            'id',
            $this->collectionFactory,
            $this->request,
            $this->cache,
            [],
            [],
            $this->positionResolver
        );
    }

    /**
     * Test case for add position data
     *
     * @param array $positions
     * @param string $categoryId
     * @param array $items
     * @dataProvider addPositionDataProvider
     */
    public function testAddPositionData(array $positions, string $categoryId, array $items): void
    {
        $this->cache
            ->expects($this->any())
            ->method('getPositions')
            ->with($this->positionCacheKey)
            ->willReturn($positions);
        $this->request
            ->expects($this->any())
            ->method('getParam')
            ->with('category_id')
            ->willReturn($categoryId);
        $this->collection
            ->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($items));
        $this->positionResolver
            ->expects($this->any())
            ->method('getPositions')
            ->with($categoryId)
            ->willReturn($positions);
        $this->assertNull($this->model->addPositionData());
    }

    /**
     * Provides variants for item collections
     *
     * @return array
     */
    public function addPositionDataProvider()
    {
        $itemMock1 = $this->getItemMock('5');
        $itemMock2 = $this->getItemMock('6');
        $this->positionCacheKey = '6xYGHn876';
        return [
            'test position data when cache key not null' => [['4', '8'], '2', [$itemMock1, $itemMock2]],
            'test position data when positions null' => [[], '2', [$itemMock1, $itemMock2]],
            'test position data when collection null' => [['4', '8'], '2', []],
            'test position data when collections not null' => [['5', '6'], '1', [$itemMock1, $itemMock2]]
        ];
    }

    /**
     * Test case for get data
     *
     * @param array $positions
     * @param string $categoryId
     * @param array $items
     * @param array $allIds
     * @dataProvider getDataProvider
     */
    public function testGetData(array $positions, string $categoryId, array $items, array $allIds): void
    {
        $limitationFilterMock = $this->getMockBuilder(ProductLimitation::class)
            ->onlyMethods(['setUsePriceIndex'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cache
            ->expects($this->any())
            ->method('getPositions')
            ->with($this->positionCacheKey)
            ->willReturn($positions);
        $this->request
            ->expects($this->any())
            ->method('getParam')
            ->with('category_id')
            ->willReturn($categoryId);
        $this->collection
            ->expects($this->any())
            ->method('setStoreId')
            ->with(Store::DEFAULT_STORE_ID)
            ->willReturnSelf();
        $this->collection
            ->expects($this->any())
            ->method('getLimitationFilters')
            ->willReturn($limitationFilterMock);
        $this->collection
            ->expects($this->never())
            ->method('getSize');
        $this->collection
            ->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($items));
        $this->collection
            ->expects($this->any())
            ->method('getAllIds')
            ->willReturn($allIds);
        $this->collection
            ->expects($this->any())
            ->method('getItems')
            ->willReturn($items);
        $limitationFilterMock
            ->expects($this->any())
            ->method('setUsePriceIndex')
            ->willReturnSelf();
        $this->positionResolver
            ->expects($this->any())
            ->method('getPositions')
            ->with($categoryId)
            ->willReturn($positions);
        $actualResult = $this->model->getData();
        $this->assertNotEmpty($actualResult);
        $this->assertCount(4, $actualResult);
        $this->assertEquals($actualResult['selectedData'], $positions);
        $this->assertEquals(count($allIds), $actualResult['totalRecords']);
    }

    /**
     * Provides variants for getData dataProvider
     *
     * @return array
     */
    public function getDataProvider()
    {
        $itemMock1 = $this->getItemMock('5');
        $itemMock2 = $this->getItemMock('6');
        $this->positionCacheKey = '6xYGHn876';
        return [
            'test position data when cache key not null' => [['4', '8'], '2', [$itemMock1, $itemMock2], ['5', '6']],
            'test position data when positions null' => [[], '2', [$itemMock1, $itemMock2], ['5', '6']],
            'test position data when collection null' => [['4', '8'], '2', [], []],
            'test position data when collections not null' => [['5', '6'], '1', [$itemMock1, $itemMock2], ['5', '6']]
        ];
    }

    /**
     * Get item mock for individual item
     *
     * @param $entityId
     * @return AbstractItem
     */
    private function getItemMock($entityId): AbstractItem
    {
        $itemMock = $this->getMockBuilder(AbstractItem::class)
            ->onlyMethods(['getEntityId'])
            ->addMethods(['setPosition', 'setIds'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $itemMock
            ->expects($this->any())
            ->method('getEntityId')
            ->willReturn($entityId);
        $itemMock
            ->expects($this->any())
            ->method('setPosition')
            ->willReturnSelf();
        $itemMock
            ->expects($this->any())
            ->method('setIds')
            ->willReturnSelf();
        return $itemMock;
    }
}
