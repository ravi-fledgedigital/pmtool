<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\ResourceModel\Db\Context as ResourceModelContext;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\ResourceModel\Attribute;
use Magento\SalesArchive\Model\ArchivalList;
use Magento\SalesArchive\Model\Archive;
use Magento\SalesArchive\Model\Config;
use Magento\SalesArchive\Model\ResourceModel\Archive as ArchiveResourceModel;
use Magento\SalesSequence\Model\Manager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ArchiveTest extends TestCase
{
    /**
     * @var Archive
     */
    protected $archive;

    /**
     * @var Archive|MockObject
     */
    protected $archiveMock;

    /**
     * @var MockObject ///\Magento\SalesArchive\Model\ResourceModel\Archive|
     */
    protected $resourceArchiveMock;

    /**
     * @var ResourceConnection|MockObject
     */
    protected $resourceMock;

    /**
     * @var Config|MockObject
     */
    protected $configMock;

    /**
     * @var ArchivalList|MockObject
     */
    protected $archivalListMock;

    /**
     * @var DateTime|MockObject
     */
    protected $dateTimeMock;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(ResourceConnection::class);

        $this->configMock = $this->createMock(Config::class);

        $this->archivalListMock = $this->createMock(ArchivalList::class);

        $this->dateTimeMock = $this->createMock(DateTime::class);

        $contextMock = $this->createMock(ResourceModelContext::class);
        $contextMock->expects($this->once())->method('getResources')->willReturn($this->resourceMock);
        $attributeMock = $this->createMock(Attribute::class);
        $sequenceManagerMock = $this->createMock(Manager::class);
        $entitySnapshotMock = $this->createMock(
            Snapshot::class
        );
        $entityRelationMock = $this->createMock(
            RelationComposite::class
        );

        $this->resourceArchiveMock = $this->getMockBuilder(ArchiveResourceModel::class)
            ->setConstructorArgs(
                [
                    $contextMock,
                    $entitySnapshotMock,
                    $entityRelationMock,
                    $attributeMock,
                    $sequenceManagerMock,
                    $this->configMock,
                    $this->archivalListMock,
                    $this->dateTimeMock
                ]
            )
            ->onlyMethods(
                [
                    'getIdsInArchive',
                    'beginTransaction',
                    'removeFromArchive',
                    'commit',
                    'rollback'
                ]
            )
            ->getMock();

        $contextMock = $this->createMock(ResourceModelContext::class);
        $contextMock->expects($this->once())->method('getResources')->willReturn($this->resourceMock);

        $objectManager = new ObjectManager($this);
        $this->archive = $objectManager->getObject(
            ArchiveResourceModel::class,
            [
                'context' => $contextMock,
                'attribute' => $attributeMock,
                'sequenceManager' => $sequenceManagerMock,
                'entitySnapshot' => $entitySnapshotMock,
                'salesArchiveConfig' => $this->configMock,
                'archivalList' => $this->archivalListMock,
                'dateTime' => $this->dateTimeMock
            ]
        );
    }

    /**
     * @return array
     */
    private function getEntityNames(): array
    {
        return [
            ArchivalList::ORDER,
            ArchivalList::INVOICE,
            ArchivalList::SHIPMENT,
            ArchivalList::CREDITMEMO
        ];
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testRemoveOrdersFromArchiveById(): void
    {
        $ids = [100021, 100023, 100054];
        $entity = 'entity_id';
        $order = 'order_id';

        $this->resourceArchiveMock->expects($this->once())
            ->method('beginTransaction')->willReturnSelf();
        $this->archivalListMock->expects($this->once())
            ->method('getEntityNames')
            ->willReturn($this->getEntityNames());
        $this->resourceArchiveMock
            ->method('getIdsInArchive')
            ->willReturnCallback(function ($arg1, $arg2) use ($ids) {
                if ($arg1 == ArchivalList::ORDER && $arg2 == $ids) {
                    return $ids;
                } elseif ($arg1 == ArchivalList::INVOICE && $arg2 == $ids) {
                    return $ids;
                } elseif ($arg1 == ArchivalList::SHIPMENT && $arg2 == $ids) {
                    return $ids;
                } elseif ($arg1 == ArchivalList::CREDITMEMO && $arg2 == $ids) {
                    return $ids;
                }
            });
        $this->resourceArchiveMock
            ->method('commit')
            ->willReturn($this->resourceArchiveMock);
        $this->resourceArchiveMock
            ->method('removeFromArchive')
            ->willReturnCallback(function ($arg1, $arg2, $arg3) use ($entity, $ids, $order) {
                if ($arg1 == ArchivalList::ORDER && $arg2 == $entity && $arg3 == $ids) {
                    return $this->resourceArchiveMock;
                } elseif ($arg1 == ArchivalList::INVOICE && $arg2 == $order && $arg3 == $ids) {
                    return $this->resourceArchiveMock;
                } elseif ($arg1 == ArchivalList::SHIPMENT && $arg2 == $order && $arg3 == $ids) {
                    return $this->resourceArchiveMock;
                } elseif ($arg1 == ArchivalList::CREDITMEMO && $arg2 == $order && $arg3 == $ids) {
                    return $this->resourceArchiveMock;
                }
            });
        $result = $this->resourceArchiveMock->removeOrdersFromArchiveById($ids);
        $this->assertEquals($ids, $result);
    }

    /**
     * @return void
     */
    public function testRemoveOrdersFromArchiveByIdException(): void
    {
        $this->expectException('Exception');
        $ids = [100021, 100023, 100054];
        $entity = 'entity_id';

        $this->archivalListMock->expects($this->once())
            ->method('getEntityNames')
            ->willReturn($this->getEntityNames());
        $this->resourceArchiveMock->expects($this->once())
            ->method('getIdsInArchive')
            ->with(ArchivalList::ORDER, $ids)
            ->willReturn($ids);
        $this->resourceArchiveMock->expects($this->once())
            ->method('beginTransaction')->willReturnSelf();
        $this->resourceArchiveMock->expects($this->once())
            ->method('removeFromArchive')
            ->with(ArchivalList::ORDER, $entity, $ids)
            ->willThrowException(new \Exception());
        $this->resourceArchiveMock->expects($this->once())
            ->method('rollback');

        $result = $this->resourceArchiveMock->removeOrdersFromArchiveById($ids);
        $this->assertInstanceOf('Exception', $result);
    }
}
