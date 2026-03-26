<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Actions\Condition\Product\Attributes;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes\SqlBuilder;
use Magento\TargetRule\Model\ResourceModel\Index;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SqlBuilderTest extends TestCase
{
    /**
     * @var SqlBuilder|MockObject
     */
    private $sqlBuilder;

    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPoolMock;

    /**
     * @var Index|MockObject
     */
    private $indexResourceMock;

    /**
     * @var Select|MockObject
     */
    private $selectMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var Attributes|MockObject
     */
    private $attributesMock;

    /**
     * @var Attribute|MockObject
     */
    private $eavAttributeMock;

    protected function setUp(): void
    {
        $this->indexResourceMock = $this->getMockBuilder(Index::class)
            ->addMethods(['getResource', 'getStoreId'])
            ->onlyMethods(
                [
                    'getTable',
                    'bindArrayOfIds',
                    'getOperatorCondition',
                    'getOperatorBindCondition',
                    'select',
                    'getConnection'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIfNullSql', 'getCheckSql'])
            ->getMockForAbstractClass();

        $this->selectMock = $this->createPartialMock(
            Select::class,
            ['from', 'assemble', 'where', 'joinLeft']
        );
        $this->metadataPoolMock = $this->createPartialMock(
            MetadataPool::class,
            ['getMetadata']
        );
        $this->eavAttributeMock = $this->createPartialMock(
            Attribute::class,
            ['isScopeGlobal', 'isStatic', 'getBackendTable', 'getId']
        );
        $this->attributesMock = $this->getMockBuilder(Attributes::class)
            ->addMethods(['getValueType'])
            ->onlyMethods(['getAttributeObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $storeMock = $this->createMock(StoreInterface::class);
        $storeManagerMock->method('getDefaultStoreView')->willReturn($storeMock);

        $this->sqlBuilder = new SqlBuilder($this->metadataPoolMock, $this->indexResourceMock, $storeManagerMock);
    }

    public function testGenerateWhereClauseForStaticAttribute()
    {
        $attributesValue = '1,2';
        $attributesNormalizedValue = [1,2];
        $attributesOperator = '()';
        $attribute = 'filter';
        $bind = [];
        $expectedClause = "e.row_id IN (1,2)";
        $this->attributesMock->setOperator($attributesOperator);
        $this->attributesMock->setAttribute($attribute);
        $this->attributesMock->setValue($attributesValue);

        $this->eavAttributeMock->expects($this->once())
            ->method('isStatic')
            ->willReturn(true);

        $this->connectionMock->expects($this->once())
            ->method('select')
            ->willReturn($this->selectMock);
        $this->indexResourceMock->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->indexResourceMock->expects($this->once())
            ->method('getOperatorCondition')
            ->with('e.' . $attribute, $attributesOperator, $attributesNormalizedValue)
            ->willReturn($expectedClause);

        $this->attributesMock->expects($this->any())
            ->method('getAttributeObject')
            ->willReturn($this->eavAttributeMock);
        $this->attributesMock->expects($this->exactly(2))
            ->method('getValueType')
            ->willReturn(Attributes::VALUE_TYPE_CONSTANT);

        $resultClause = $this->sqlBuilder->generateWhereClause(
            $this->attributesMock,
            $bind
        );

        $this->assertEquals("({$expectedClause})", $resultClause);
    }
}
