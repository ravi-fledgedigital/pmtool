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

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Replaces configuration and environment variables in the provided string pattern
 */
class VariablesResolver implements VariablesResolverInterface
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(private ScopeConfigInterface $scopeConfig)
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $inputPattern): string
    {
        if (empty($inputPattern)) {
            return '';
        }

        return preg_replace_callback(
            sprintf(
                '/(?<variable>{(%s|%s).*?})/',
                preg_quote(self::PATTERN_CONFIG_VARIABLE),
                preg_quote(self::PATTERN_ENV_VARIABLE)
            ),
            function ($matches) {
                $variable = trim($matches['variable'], '{} ');

                if (str_starts_with($variable, self::PATTERN_ENV_VARIABLE)) {
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                    $result = getenv(str_replace(self::PATTERN_ENV_VARIABLE, '', $variable)) ?: '';
                } else {
                    $configPath = str_replace(self::PATTERN_CONFIG_VARIABLE, '', $variable);
                    $result = $this->scopeConfig->getValue(...explode(':', $configPath));
                }

                if (is_array($result)) {
                    return implode(' ', $result);
                }

                return is_string($result) ? $result : '';
            },
            $inputPattern,
        );
    }
}
