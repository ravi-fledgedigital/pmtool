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

namespace Magento\AdobeCommerceWebhooks\Model\Config;

/**
 * Replaces configuration and environment variables in the provided string pattern
 */
interface VariablesResolverInterface
{
    public const PATTERN_ENV_VARIABLE = 'env:';
    public const PATTERN_CONFIG_VARIABLE = 'config:';

    /**
     * Processes string pattern with variables.
     *
     * Checks if the value contains environment and configuration variable and replaces them accordingly,
     * otherwise return value without changes.
     * For example, in the next string "Bearer {env:AUTH_TOKEN}" the {env:AUTH_TOKEN} part will be replaced with
     * AUTH_TOKEN environment variable.
     *
     * @param string $inputPattern
     * @return string
     */
    public function resolve(string $inputPattern): string;
}
