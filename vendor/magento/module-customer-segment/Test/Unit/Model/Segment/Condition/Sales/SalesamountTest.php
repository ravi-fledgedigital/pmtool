<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition\Sales;

use Magento\CustomerSegment\Model\ConditionFactory;
use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\CustomerSegment\Model\Segment\Condition\Sales\Salesamount;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Layout;
use Magento\Rule\Model\Condition\Context;
use Magento\Sales\Model\ResourceModel\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests orders amount condition
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SalesamountTest extends TestCase
{
    /**
     * @var Salesamount
     */
    protected $model;

    /**
     * @var Order|MockObject
     */
    protected $orderResourceMock;

    /**
     * @var ConditionFactory|MockObject
     */
    protected $conditionFactoryMock;

    /**
     * @var Segment|MockObject
     */
    protected $resourceSegment;

    /**
     * @var Layout|MockObject
     */
    protected $layout;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->orderResourceMock = $this->createMock(Order::class);
        $this->layout = $this->createMock(Layout::class);
        $ruleContextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $ruleContextMock->method('getLayout')->willReturn($this->layout);
        $this->resourceSegment =
            $this->createMock(Segment::class);

        $this->conditionFactoryMock = $this->createMock(ConditionFactory::class);

        $this->model = $objectManager->getObject(
            Salesamount::class,
            [
                'context' => $ruleContextMock,
                'orderResource' => $this->orderResourceMock,
                'conditionFactory' => $this->conditionFactoryMock,
                'resourceSegment' => $this->resourceSegment
            ]
        );
    }

    /**
     * @dataProvider conditionProvider
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testGetConditionsSql($operator, $value, $attribute, $checkSql)
    {
        $website = 1;
        $salesOrderTable = 'sales_order_table';
        $storeTable = 'store_table';
        $checkSqlResult = $checkSql . ' ' . $operator . ' ' . (double)$value;
        $storeIds = [1, 2];

        $this->model->setData('operator', $operator);
        $this->model->setData('value', $value);
        $this->model->setData('attribute', $attribute);

        $select = $this->createMock(Select::class);
        $select->expects($this->once())
            ->method('from')
            ->with(['sales_order' => $salesOrderTable], ['sales_order.customer_id'])
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('group')
            ->with(['sales_order.customer_id'])
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('having')
            ->with(new \Zend_Db_Expr($checkSqlResult))
            ->willReturnSelf();
        $select->expects($this->exactly(2))
            ->method('where')
            ->willReturnCallback(function ($arg1, $arg2) use ($storeIds, $select) {
                if ($arg1 == 'sales_order.customer_id IS NOT NULL') {
                    return $select;
                } elseif ($arg1 == 'store.website_id IN (?)' && $arg2 == $storeIds) {
                    return $select;
                }
            });

        $storeSelect = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeSelect->expects($this->once())
            ->method('from')
            ->with(['store' => $storeTable], ['store.store_id'])
            ->willReturnSelf();
        $storeSelect->expects($this->once())
            ->method('where')
            ->with('store.website_id IN (?)', $website)
            ->willReturnSelf();

        $this->resourceSegment->expects($this->exactly(2))
            ->method('createSelect')
            ->willReturnOnConsecutiveCalls($select, $storeSelect);

        $this->resourceSegment->expects($this->once())
            ->method('getSqlOperator')
            ->willReturn($operator);

        $connection = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->resourceSegment->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('fetchCol')
            ->with($storeSelect)
            ->willReturn($storeIds);
        $connection->expects($this->once())
            ->method('quote')
            ->with((double) $value)
            ->willReturn((double) $value);
        //for getConditionSql()
        $connection->expects($this->exactly(2))
            ->method('getCheckSql')
            ->willReturnCallback(function ($arg1, $arg2, $arg3) use ($checkSql, $checkSqlResult) {
                if ($arg1 === $checkSql . ' IS NOT NULL' && $arg2 == $checkSql && $arg3 === 0) {
                    return $checkSql;
                } elseif ($arg1 == $checkSqlResult && $arg2 == 1 && $arg3 == 0) {
                    return $checkSqlResult;
                }
            });

        $this->resourceSegment->expects($this->exactly(2))
            ->method('getTable')
            ->willReturnMap([['sales_order', $salesOrderTable], ['store', $storeTable]]);

        $this->assertEquals($select, $this->model->getConditionsSql(null, 1, false));
    }

    public static function conditionProvider()
    {
        return [
            ['>', null, 'total', 'SUM(sales_order.base_grand_total)'],
            ['=', 0, 'average', 'AVG(sales_order.base_grand_total)'],
            ['<', 1, 'total', 'SUM(sales_order.base_grand_total)']
        ];
    }
}
