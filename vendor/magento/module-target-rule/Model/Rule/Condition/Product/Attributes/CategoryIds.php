<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Model\Rule\Condition\Product\Attributes;

use Magento\Framework\DB\Select;
use Magento\Rule\Model\Condition\Product\AbstractProduct as ProductCondition;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes;
use Magento\TargetRule\Model\ResourceModel\Index as IndexResource;
use Zend_Db_Expr;

/**
 * Build conditions for collection with category_ids attribute
 *
 * - For conditions like (e.g "Product Category contains Constant Value 6"),
 *  the generated SQL will look like the following:
 *  (SELECT COUNT(*) FROM `catalog_category_product` WHERE ... AND `category_id` IN ('6')) > 0
 * - For conditions like (e.g "Product Category does not contain  Constant Value 6"),
 *  the generated SQL will look like the following:
 *  (SELECT COUNT(*) FROM `catalog_category_product` WHERE ... AND `category_id` IN ('6')) = 0
 */
class CategoryIds implements SqlBuilderInterface
{
    private const CONDITIONS_OPERATOR_MAP = [
        '!{}' => '!()',
        '!=' => '!()',
        '{}' => '()',
        '==' => '()',
    ];

    /**
     * @param IndexResource $indexResource
     */
    public function __construct(
        private IndexResource $indexResource
    ) {
    }

    /**
     * @inheritdoc
     */
    public function generateWhereClause(
        ProductCondition $condition,
        array &$bind,
        int $storeId,
        Select $select
    ): Zend_Db_Expr {
        $select->from(
            $this->indexResource->getTable('catalog_category_product'),
            'COUNT(*)'
        )->where(
            'product_id = e.entity_id'
        );
        $expr = match ($condition->getValueType()) {
            Attributes::VALUE_TYPE_SAME_AS => $this->generateSameAsExpression($condition, $bind, $select),
            Attributes::VALUE_TYPE_CHILD_OF => $this->generateChildOfExpression($condition, $bind, $select),
            default => $this->generateExpression($condition, $select),
        };

        return new Zend_Db_Expr(sprintf($expr, $select->assemble()));
    }

    /**
     * Generate expression for "Same as" value type.
     *
     * @param ProductCondition $condition
     * @param array $bind
     * @param Select $select
     * @return string
     */
    private function generateSameAsExpression(ProductCondition $condition, array &$bind, Select $select): string
    {
        $operator = self::CONDITIONS_OPERATOR_MAP[$condition->getOperator()] ?? $condition->getOperator();
        $expr = '(%s) > 0';
        if ($operator === '!()') {
            $operator = '()';
            $expr = '(%s) = 0';
        }
        $where = $this->indexResource->getOperatorBindCondition(
            'category_id',
            'category_ids',
            $operator,
            $bind,
            ['bindArrayOfIds']
        );
        $select->where($where);

        return $expr;
    }

    /**
     * Generate expression for "Child of" value type.
     *
     * @param ProductCondition $condition
     * @param array $bind
     * @param Select $select
     * @return string
     */
    private function generateChildOfExpression(ProductCondition $condition, array &$bind, Select $select): string
    {
        $concatenated = $this->indexResource->getConnection()->getConcatSql(['tp.path', "'/%'"]);
        $subSelect = $this->indexResource->select()->from(
            ['tc' => $this->indexResource->getTable('catalog_category_entity')],
            'entity_id'
        )->join(
            ['tp' => $this->indexResource->getTable('catalog_category_entity')],
            "tc.path " . ($condition->getOperator() == '!()' ? 'NOT ' : '') . "LIKE {$concatenated}",
            []
        )->where(
            $this->indexResource->getOperatorBindCondition(
                'tp.entity_id',
                'category_ids',
                '()',
                $bind,
                ['bindArrayOfIds']
            )
        );
        $select->where('category_id IN (?)', $subSelect);

        return '(%s) > 0';
    }

    /**
     * Generate general expression.
     *
     * @param ProductCondition $condition
     * @param Select $select
     * @return string
     */
    private function generateExpression(ProductCondition $condition, Select $select): string
    {
        $operator = self::CONDITIONS_OPERATOR_MAP[$condition->getOperator()] ?? $condition->getOperator();
        $expr = '(%s) > 0';
        if ($operator === '!()') {
            $operator = '()';
            $expr = '(%s) = 0';
        }
        $value = $this->indexResource->bindArrayOfIds($condition->getValue());
        $where = $this->indexResource->getOperatorCondition('category_id', $operator, $value);
        $select->where($where);

        return $expr;
    }
}
