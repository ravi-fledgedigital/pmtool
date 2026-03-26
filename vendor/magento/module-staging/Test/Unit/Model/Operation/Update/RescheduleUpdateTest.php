<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Operation\Update;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\EntityManager\HydratorPool;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Operation\Update\RescheduleUpdate;
use Magento\Staging\Model\VersionInfo;
use Magento\Staging\Model\VersionInfoFactory;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class RescheduleUpdateTest extends TestCase
{
    /**
     * @var MockObject|ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var MockObject|MetadataPool
     */
    private $metadataPool;

    /**
     * @var MockObject|HydratorPool
     */
    private $hydratorPool;

    /**
     * @var MockObject|TypeResolver
     */
    private $typeResolver;

    /**
     * @var MockObject|UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var RescheduleUpdate
     */
    private $rescheduleUpdate;

    /**
     * @var VersionInfo|MockObject
     */
    private $versionInfoMock;

    /**
     * @var VersionInfoFactory
     */
    private $versionInfoFactoryMock;

    /**
     * @var EntityMetadataInterface
     */
    private $metadataMock;

    /**
     * @var HydratorInterface
     */
    private $hydratorMock;

    protected function setUp(): void
    {
        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->hydratorPool = $this->getMockBuilder(HydratorPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->typeResolver = $this->getMockBuilder(TypeResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateRepository = $this->getMockBuilder(UpdateRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->versionInfoFactoryMock = $this->getMockBuilder(VersionInfoFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->hydratorMock = $this->getMockBuilder(HydratorInterface::class)
            ->getMockForAbstractClass();
        $this->versionInfoMock = $this->getMockBuilder(VersionInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)
            ->getMockForAbstractClass();
        $this->rescheduleUpdate = new RescheduleUpdate(
            $this->resourceConnection,
            $this->metadataPool,
            $this->hydratorPool,
            $this->typeResolver,
            $this->updateRepository,
            $this->versionInfoFactoryMock
        );
    }

    /**
     * @throws \Exception
     */
    public function testReschedule()
    {
        $originVersion = 1;
        $targetVersion = 256;
        $idField = 'id';
        $linkField = 'link_id';
        $data = [
            $idField => 42,
            $linkField => 50
        ];
        $expectedData = [$linkField => 50, $idField => $data[$idField],'created_in' => 256, 'updated_in' => 512];
        $entity = new \stdClass();
        $updateMock = $this->getMockBuilder(UpdateInterface::class)
            ->getMockForAbstractClass();
        $updateTargetMock = $this->getMockBuilder(UpdateInterface::class)
            ->getMockForAbstractClass();
        $updateTargetMock->method('getRollbackId')
            ->willReturn(512);
        $this->metadataMock->expects($this->atLeastOnce())
            ->method('getIdentifierField')
            ->willReturn($idField);
        $this->metadataMock->expects($this->atLeastOnce())->method('getLinkField')->willReturn($linkField);
        $this->hydratorMock->expects($this->atLeastOnce())->method('extract')->with($entity)->willReturn($data);

        $adapterMock = $this->getMockBuilder(AdapterInterface::class)
            ->getMockForAbstractClass();
        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                "from",
                "where",
                "order",
                "limit",
                "setPart",
            ])
            ->getMock();
        $selectMock->expects($this->any())->method("from")->willReturnSelf();
        $selectMock->expects($this->any())->method("order")->willReturnSelf();
        $selectMock->expects($this->any())->method("limit")->willReturnSelf();
        $selectMock->expects($this->any())->method("setPart")->willReturnSelf();
        $this->updateRepository->expects($this->exactly(2))->method('get')
            ->willReturnCallback(function ($arg1) use ($originVersion, $targetVersion, $updateMock, $updateTargetMock) {
                if ($arg1 == $originVersion) {
                    return $updateMock;
                } elseif ($arg1 == $targetVersion) {
                    return $updateTargetMock;
                }
            });
        $this->metadataPool->expects($this->atLeastOnce())
            ->method('getMetadata')
            ->willReturn($this->metadataMock);
        $this->hydratorPool->expects($this->atLeastOnce())
            ->method('getHydrator')
            ->willReturn($this->hydratorMock);
        $this->resourceConnection->expects($this->atLeastOnce())
            ->method('getConnectionByName')
            ->willReturn($adapterMock);
        $adapterMock->expects($this->atLeastOnce())->method('select')->willReturn($selectMock);
        $updateMock->expects($this->atLeastOnce())->method('getId')->willReturn($originVersion);
        $updateMock->expects($this->once())->method('getRollbackId')->willReturn(null);
        $updateTargetMock->expects($this->atLeastOnce())->method('getId')->willReturn($targetVersion);
        $selectMock->expects($this->atLeastOnce())->method("where")
            ->willReturnCallback(function ($arg1, $arg2 = null) use ($selectMock, $originVersion, $targetVersion) {
                if ($arg1 === 't.created_in < ?' && $arg2 === $originVersion) {
                    return $selectMock;
                } elseif ($arg1 === 't.id = ?') {
                    return $selectMock;
                } elseif ($arg1 === 't.created_in != ?' && $arg2 === $originVersion) {
                    return $selectMock;
                } elseif ($arg1 === 't.created_in > ?' && $arg2 === $originVersion) {
                    return $selectMock;
                } elseif ($arg1 === 't.created_in < ?' && $arg2 === $targetVersion) {
                    return $selectMock;
                } elseif ($arg1 === 't.created_in != ?' && $arg2 === $originVersion) {
                    return $selectMock;
                } elseif ($arg1 === 't.id = ?') {
                    return $selectMock;
                } elseif ($arg1 === 't.created_in != ?' && $arg2 === $originVersion) {
                    return $selectMock;
                }
            });
        $adapterMock->expects($this->atLeastOnce())
            ->method('update')
            ->willReturn(1);
        $this->versionInfoFactoryMock->expects($this->once())->method('create')
            ->with(
                [
                    'rowId' => $expectedData[$linkField],
                    'identifier' => $expectedData[$idField],
                    'createdIn' => $expectedData['created_in'],
                    'updatedIn' => $expectedData['updated_in']
                ]
            )
            ->willReturn($this->versionInfoMock);
        $this->rescheduleUpdate->reschedule($originVersion, $targetVersion, $entity);
    }

    public function testRescheduleWithPurge()
    {
        $originVersion = 1;
        $targetVersion = 256;
        $idField = 'id';
        $linkField = 'link_id';
        $data = [
            $idField => 42,
            $linkField => 50,
            'updated_in' => 512
        ];
        $expectedData = [$linkField => 50, $idField => $data[$idField],'created_in' => 256, 'updated_in' => 512];
        $entity = new \stdClass();
        $updateMock = $this->getMockBuilder(UpdateInterface::class)
            ->getMockForAbstractClass();
        $updateTargetMock = $this->getMockBuilder(UpdateInterface::class)
            ->getMockForAbstractClass();
        $this->metadataMock->expects($this->atLeastOnce())
            ->method('getIdentifierField')
            ->willReturn($idField);
        $this->metadataMock->expects($this->atLeastOnce())->method('getLinkField')->willReturn($linkField);
        $this->hydratorMock->expects($this->atLeastOnce())->method('extract')->with($entity)->willReturn($data);

        $adapterMock = $this->getMockBuilder(AdapterInterface::class)
            ->getMockForAbstractClass();
        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->expects($this->any())->method("from")->willReturnSelf();
        $selectMock->expects($this->any())->method("order")->willReturnSelf();
        $selectMock->expects($this->any())->method("limit")->willReturnSelf();
        $selectMock->expects($this->any())->method("setPart")->willReturnSelf();
        $this->updateRepository->expects($this->exactly(3))
            ->method('get')
            ->willReturnOnConsecutiveCalls($updateMock, $updateTargetMock);
        $this->metadataPool->expects($this->atLeastOnce())
            ->method('getMetadata')
            ->willReturn($this->metadataMock);
        $this->hydratorPool->expects($this->atLeastOnce())
            ->method('getHydrator')
            ->willReturn($this->hydratorMock);
        $this->resourceConnection->expects($this->atLeastOnce())
            ->method('getConnectionByName')
            ->willReturn($adapterMock);
        $adapterMock->expects($this->atLeastOnce())->method('select')->willReturn($selectMock);
        $updateMock->expects($this->atLeastOnce())->method('getId')->willReturn($originVersion);
        $updateMock->expects($this->exactly(2))->method('getRollbackId')->willReturn(null);
        $updateTargetMock->expects($this->atLeastOnce())->method('getId')->willReturn($targetVersion);
        $updateTargetMock->expects($this->exactly(2))->method('getRollbackId')->willReturn(null);
        $selectMock->expects($this->atLeastOnce())->method("where")->willReturnSelf();
        $adapterMock->expects($this->atLeastOnce())
            ->method('update')
            ->willReturn(1);
        $adapterMock->expects($this->once())->method('delete')->willReturn(1);
        $this->versionInfoFactoryMock->expects($this->once())->method('create')
            ->with(
                [
                    'rowId' => $expectedData[$linkField],
                    'identifier' => $expectedData[$idField],
                    'createdIn' => $expectedData['created_in'],
                    'updatedIn' => VersionManager::MAX_VERSION
                ]
            )
            ->willReturn($this->versionInfoMock);
        $this->rescheduleUpdate->reschedule($originVersion, $targetVersion, $entity);
    }
}
