<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\ResourceModel\Order;
use Magento\Sales\Model\Order\Creditmemo as CreditMemoModel;
use Magento\SalesArchive\Model\Config;
use Magento\SalesArchive\Model\ResourceModel\Archive;
use Magento\SalesArchive\Observer\ArchiveGridAsyncUpdateObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ArchiveGridAsyncUpdateObserverTest extends TestCase
{
    /**
     * @var ArchiveGridAsyncUpdateObserver
     */
    protected $observerModel;

    /**
     * @var MockObject
     */
    private $globalConfigMock;

    /**
     * @var MockObject
     */
    private $configMock;

    /**
     * @var MockObject
     */
    private $resourceMock;

    /**
     * @var MockObject
     */
    private $archiveMock;

    /**
     * @var OrderModel|MockObject
     */
    private $order;

    /**
     * @var CreditMemoModel|MockObject
     */
    private $creditMemo;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->archiveMock = $this->createMock(Archive::class);
        $this->globalConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->resourceMock = $this->createMock(Order::class);
        $this->creditMemo = $this->getMockBuilder(CreditMemoModel::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getOrder'
                ]
            )
            ->getMock();
        $this->order = $this->getMockBuilder(OrderModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getId',
                    'getStatus',
                    'getIdFieldName'
                ]
            )
            ->getMock();
        $this->observerModel = new ArchiveGridAsyncUpdateObserver(
            $this->configMock,
            $this->archiveMock,
            $this->globalConfigMock
        );
    }

    public function testExecuteIfArchiveEntityExists()
    {
        $entityId = 1;
        $this->configMock->expects($this->once())->method('isArchiveActive')->willReturn(true);
        $observerMock = $this->createMock(Observer::class);
        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getCreditmemo'])
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock->expects($this->exactly(1))->method('getEvent')->willReturn($eventMock);
        $eventMock->expects($this->once())->method('getCreditmemo')->willReturn($this->creditMemo);
        $this->creditMemo->method('getOrder')
            ->willReturn($this->order);
        $connectionMock = $this->getMockForAbstractClass(
            Archive::class,
            [],
            '',
            false,
            false,
            true,
            ['update', 'quoteInto']
        );
        $this->archiveMock->expects($this->exactly(1))->method('getConnection')->willReturn($connectionMock);

        $this->globalConfigMock
            ->expects($this->once())
            ->method('isSetFlag')
            ->with('dev/grid/async_indexing')
            ->willReturn(true);

        $this->order->expects($this->any())->method('getId')->willReturn($entityId);
        $this->order->expects($this->any())->method('getStatus')->willReturn('closed');
        $this->order->expects($this->any())->method('getIdFieldName')->willReturn('status');
        $this->archiveMock
            ->expects($this::once())
            ->method('getArchiveEntityTable')
            ->willReturn('magento_sales_order_grid_archive');
        $conditionSql = "entity_id = $entityId";
        $connectionMock->expects($this->once())->method('quoteInto')
            ->willReturn($conditionSql);
        $connectionMock->expects($this->once())->method('update');
        $this->observerModel->execute($observerMock);
    }
}
