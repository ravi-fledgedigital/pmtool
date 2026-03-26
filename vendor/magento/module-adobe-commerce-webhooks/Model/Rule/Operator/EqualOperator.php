<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Model\Rule\Operator;

use Magento\AdobeCommerceWebhooks\Model\Rule\OperatorException;
use Magento\AdobeCommerceWebhooks\Model\Rule\OperatorInterface;

/**
 * Verifies that checked value is matching value from the rule.
 */
class EqualOperator implements OperatorInterface
{
    /**
     * Verifies that checked value is matching value from the rule.
     *
     * @param mixed $fieldValue
     * @param string|null $ruleValue
     * @return bool
     * @throws OperatorException
     */
    public function verify(mixed $fieldValue, ?string $ruleValue = null): bool
    {
        if (is_array($fieldValue) || (string)$fieldValue != $fieldValue) {
            throw new OperatorException(__('Input data must be in string format or can be converted to string'));
        }

        return $ruleValue == (string)$fieldValue;
    }
}
