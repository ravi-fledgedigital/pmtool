<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model\Actions\Condition\Product\Attributes;

use Magento\TargetRule\Model\Actions\Condition\Product\Attributes;

/**
 * Target rule SQL builder is used to construct SQL conditions for 'products to display'.
 */
class SqlBuilder extends \Magento\TargetRule\Model\Rule\Condition\Product\Attributes\SqlBuilder
{
    /**
     * @inheritdoc
     */
    protected function shouldUseBind($condition)
    {
        return $condition->getValueType() != Attributes::VALUE_TYPE_CONSTANT;
    }

    /**
     * @inheritdoc
     */
    protected function normalizeConditionValue($condition)
    {
        $value = $condition->getValue();
        if ($condition->getValueType() == Attributes::VALUE_TYPE_CONSTANT) {
            $operator = $condition->getOperator();
            // split value by commas into array for operators with multiple operands
            if (($operator == '()' || $operator == '!()') && is_string($value) && trim($value) != '') {
                $value = preg_split('/\s*,\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
            }
        }
        return $value;
    }
}
