<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Model\Rule\Condition\Product\Attributes;

use Magento\Framework\DB\Select;
use Magento\Rule\Model\Condition\Product\AbstractProduct as ProductCondition;
use Zend_Db_Expr;

interface SqlBuilderInterface
{
    /**
     * Generate WHERE clause based on provided condition.
     *
     * @param ProductCondition $condition
     * @param array $bind
     * @param int $storeId
     * @param Select $select
     * @return Zend_Db_Expr
     */
    public function generateWhereClause(
        ProductCondition $condition,
        array &$bind,
        int $storeId,
        Select $select
    ): Zend_Db_Expr;
}
