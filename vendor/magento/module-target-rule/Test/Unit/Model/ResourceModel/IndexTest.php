<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\ResourceModel;

use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as CatalogCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\CustomerSegment\Helper\Data as CustomerSegmentData;
use Magento\CustomerSegment\Model\Customer;
use Magento\CustomerSegment\Model\ResourceModel\Segment as CustomerSegmentModel;
use Magento\Rule\Model\Action\Collection as ActionCollection;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TargetRule\Helper\Data as TargetRuleData;
use Magento\TargetRule\Model\ResourceModel\Index as TargetRuleIndex;
use Magento\TargetRule\Model\ResourceModel\IndexPool;
use Magento\TargetRule\Model\ResourceModel\Rule;
use Magento\TargetRule\Model\ResourceModel\Index\Index;
use Magento\TargetRule\Model\ResourceModel\Rule\Collection;
use Magento\TargetRule\Model\Index as IndexModel;
use Magento\TargetRule\Model\Rule as RuleModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\TargetRule\Model\ResourceModel\Index
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexTest extends TestCase
{
    /**
     * @var TargetRuleIndex
     */
    private $model;

    /**
     * @var AdapterInterface|MockObject
     */
    private $adapterInterface;

    /**
     * @var IndexModel|MockObject
     */
    private $indexModel;

    /**
     * @var IndexPool|MockObject
     */
    private $indexPool;

    /**
     * @var CatalogCollectionFactory|MockObject
     */
    private $productCollectionFactory;

    protected function setUp(): void
    {
        $contextMock = $this->getMockBuilder(DbContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->indexPool = $this->getMockBuilder(IndexPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $ruleMock = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->getMock();
        $segmentCollectionFactoryMock = $this->getMockBuilder(CustomerSegmentModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productCollectionFactory = $this->getMockBuilder(CatalogCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $visibilityMock = $this->getMockBuilder(ProductVisibility::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sessionMock = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customerSegmentDataMock = $this->getMockBuilder(CustomerSegmentData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $targetRuleDataMock = $this->getMockBuilder(TargetRuleData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stockHelperMock = $this->getMockBuilder(Stock::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapterInterface = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $resourceMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->adapterInterface);

        $contextMock->expects($this->any())
            ->method('getResources')
            ->willReturn($resourceMock);

        $this->indexModel = $this->getMockBuilder(IndexModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);

        $this->model = $objectManager->getObject(
            TargetRuleIndex::class,
            [
                'context' => $contextMock,
                'indexPool' => $this->indexPool,
                'ruleMock' => $ruleMock,
                'segmentCollectionFactory' => $segmentCollectionFactoryMock,
                'productCollectionFactory' => $this->productCollectionFactory,
                'storeManager' => $storeManagerMock,
                'visibility' => $visibilityMock,
                'customer' => $customerMock,
                'session' => $sessionMock,
                'customerSegmentData' => $customerSegmentDataMock,
                'targetRuleData' => $targetRuleDataMock,
                'coreRegistry' => $this->getMockBuilder(Registry::class)
                    ->disableOriginalConstructor()
                    ->getMock(),
                'stockHelper' => $stockHelperMock,
            ]
        );
    }

    /**
     * @return array
     */
    public function getOperatorConditionDataProvider(): array
    {
        return [
            ['category_id', '()', ' IN(?)', [4], [4]],
            ['category_id', '!()', ' NOT IN(?)', [4], [4]],
            ['category_id', '{}', ' IN (?)', [5], [5]],
            ['category_id', '!{}', ' NOT IN (?)', [5], [5]],
            ['category_id', '{}', ' LIKE ?', 5, '%5%'],
            ['category_id', '!{}', ' NOT LIKE ?', 5, '%5%'],
            ['category_id', '>=', '>=?', 5, 5],
            ['category_id', '==', '=?', 7, 7],
            ['value', '{}', ' IN (?)', [6], [6]],
            ['value', '!{}', ' NOT IN (?)', [6], [6]],
            ['value', '{}', ' LIKE ?', 6, '%6%'],
            ['value', '!{}', ' NOT LIKE ?', 6, '%6%'],
        ];
    }

    /**
     * @param string $field
     * @param string $operator
     * @param string $expectedSelectOperator
     * @param mixed $value
     * @param mixed $expectedValue
     *
     * @dataProvider getOperatorConditionDataProvider
     *
     * @return void
     */
    public function testGetOperatorCondition(
        string $field,
        string $operator,
        string $expectedSelectOperator,
        $value,
        $expectedValue
    ): void {
        $quoteIdentifier = '`' . $field . '`';
        $this->adapterInterface->expects($this->once())
            ->method('quoteIdentifier')
            ->willReturn($quoteIdentifier);
        $this->adapterInterface->expects($this->once())
            ->method('quoteInto')
            ->with($quoteIdentifier . $expectedSelectOperator, $expectedValue);

        $this->model->getOperatorCondition($field, $operator, $value);
    }

    /**
     * @return void
     */
    public function testRuleNotSavedInTheGetter(): void
    {
        $ruleModel = $this->getMockBuilder(RuleModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $index = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$ruleModel]));
        $this->indexModel->expects($this->any())
            ->method('getRuleCollection')
            ->willReturn($collection);
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->indexModel->expects($this->any())
            ->method('getProduct')
            ->willReturn($product);

        $this->indexPool->expects($this->any())
            ->method('get')
            ->willReturn($index);
        $this->indexModel->expects($this->any())
            ->method('getExcludeProductIds')
            ->willReturn([]);
        $ruleModel->expects($this->any())
            ->method('checkDateForStore')
            ->willReturn(true);

        $productCollection = $this->getMockBuilder(ProductCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($productCollection);
        $productCollection->expects($this->any())
            ->method('setStoreId')
            ->willReturnSelf();
        $productCollection->expects($this->any())
            ->method('addPriceData')
            ->willReturnSelf();
        $productCollection->expects($this->any())
            ->method('setVisibility')
            ->willReturnSelf();
        $actionCollection = $this->getMockBuilder(ActionCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productCollection->expects($this->any())
            ->method('getSelect')
            ->willReturn($select);
        $this->adapterInterface->expects($this->any())
            ->method('fetchCol')
            ->willReturn([]);

        $ruleModel->expects($this->atLeastOnce())
            ->method('getActions')
            ->willReturn($actionCollection);
        $ruleModel->expects($this->never())
            ->method('save');

        $this->model->getProductIds($this->indexModel);
    }
}
