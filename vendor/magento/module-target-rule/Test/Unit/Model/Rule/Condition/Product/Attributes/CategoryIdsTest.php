<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Rule\Condition\Product\Attributes;

use Magento\Framework\DB\Select;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes;
use Magento\TargetRule\Model\ResourceModel\Index as IndexResource;
use Magento\TargetRule\Model\Rule\Condition\Product\Attributes\CategoryIds;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoryIdsTest extends TestCase
{
    /**
     * @var IndexResource|MockObject
     */
    private $indexResourceMock;

    /**
     * @var CategoryIds
     */
    private $model;

    protected function setUp(): void
    {
        $this->indexResourceMock = $this->createMock(IndexResource::class);
        $this->model = new CategoryIds($this->indexResourceMock);
    }

    public function testGenerateSameAsWhereClause(): void
    {
        $attributesValue = '1,2';
        $attributesOperator = '()';
        $attribute = 'category_ids';
        $bind = [];
        $categoryTable = 'catalog_category_product';
        $categoryWhere = 'category_id in (1,2)';

        $attributesMock = $this->getMockBuilder(Attributes::class)
            ->addMethods(['getValueType'])
            ->onlyMethods(['getAttributeObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $attributesMock->expects(self::atLeastOnce())
            ->method('getValueType')
            ->willReturn(Attributes::VALUE_TYPE_SAME_AS);
        $attributesMock->setOperator($attributesOperator);
        $attributesMock->setAttribute($attribute);
        $attributesMock->setValue($attributesValue);

        $this->indexResourceMock->expects(self::atLeastOnce())
            ->method('getTable')
            ->with('catalog_category_product')
            ->willReturn($categoryTable);
        $this->indexResourceMock->expects(self::once())
            ->method('getOperatorBindCondition')
            ->with(
                'category_id',
                'category_ids',
                $attributesOperator,
                $bind,
                ['bindArrayOfIds']
            )->willReturn($categoryWhere);

        $selectMock = $this->createMock(Select::class);
        $selectMock->expects(self::once())
            ->method('from')
            ->with($categoryTable, 'COUNT(*)')
            ->willReturnSelf();
        $selectMock->expects(self::exactly(2))
            ->method('where')
            ->willReturnMap([
                ['product_id=e.entity_id', null, null, $selectMock],
                [$categoryWhere, null, null, $selectMock]
            ]);
        $selectMock->expects(self::once())
            ->method('assemble')
            ->willReturn($categoryWhere);

        $resultClause = $this->model->generateWhereClause(
            $attributesMock,
            $bind,
            1,
            $selectMock
        );
        $this->assertEquals("({$categoryWhere}) > 0", $resultClause);
    }
}
