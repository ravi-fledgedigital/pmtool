<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\ResourceModel\Sales\Quote;

use Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Quote\Address;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    /**
     * @var Address
     */
    protected $address;

    /**
     * @var ResourceConnection|MockObject
     */
    protected $resourceMock;

    /**
     * @var AdapterInterface|MockObject
     */
    protected $connectionMock;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Address|MockObject
     */
    protected $parentResourceModelMock;

    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->connectionMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->parentResourceModelMock = $this->createMock(\Magento\Quote\Model\ResourceModel\Quote\Address::class);

        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->resourceMock->expects($this->any())
            ->method('getTableName')
            ->willReturnArgument(0);

        $contextMock = $this->createMock(Context::class);
        $contextMock->expects($this->once())->method('getResources')->willReturn($this->resourceMock);

        $this->address = new Address(
            $contextMock,
            $this->parentResourceModelMock
        );
    }

    public function testAttachDataToEntitiesNoItems()
    {
        $this->connectionMock->expects($this->never())
            ->method('select');
        $this->connectionMock->expects($this->never())
            ->method('fetchAll');

        $this->assertEquals($this->address, $this->address->attachDataToEntities([]));
    }
}
