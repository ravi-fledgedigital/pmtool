<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
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

namespace Magento\AdobeCommerceWebhooks\Model\Config;

/**
 * Generates a unique name for a hook rule
 */
class RuleNameGenerator
{
    /**
     * Generates a unique name for a hook rule by combining its field name and operator name
     *
     * @param string $ruleField
     * @param string $ruleOperator
     * @return string
     */
    public function generate(string $ruleField, string $ruleOperator): string
    {
        return $ruleField . ':' . $ruleOperator;
    }
}
