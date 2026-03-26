<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Entity\PeriodSync;

use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Staging\Model\Entity\PeriodSync\Scheduler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SchedulerTest extends TestCase
{
    /**
     * @var BulkManagementInterface|MockObject
     */
    private $bulkManagementMock;

    /**
     * @var IdentityGeneratorInterface|MockObject
     */
    private $identityGeneratorMock;

    /**
     * @var OperationInterfaceFactory|MockObject
     */
    private $operationFactoryMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var Scheduler
     */
    private $scheduler;

    protected function setUp(): void
    {
        $this->bulkManagementMock = $this->createMock(BulkManagementInterface::class);
        $this->identityGeneratorMock = $this->createMock(IdentityGeneratorInterface::class);
        $this->operationFactoryMock = $this->createMock(OperationInterfaceFactory::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->scheduler = new Scheduler(
            $this->bulkManagementMock,
            $this->identityGeneratorMock,
            $this->operationFactoryMock,
            $this->serializerMock
        );
    }

    public function testExecuteWithoutIds(): void
    {
        $this->operationFactoryMock->expects(self::never())
            ->method('create');
        $this->bulkManagementMock->expects(self::never())
            ->method('scheduleBulk');
        $this->scheduler->execute([]);
    }
}
