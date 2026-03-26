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

namespace Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Request;

/**
 * Sanitizes sensitive data in webhook payload
 */
class SensitiveDataSanitizer implements SensitiveDataSanitizerInterface
{
    private const SANITIZED_VALUE = '***';

    /**
     * @param array $fields
     * @param array $regexFields
     */
    public function __construct(
        private array $fields = [],
        private array $regexFields = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function sanitize(array $data): array
    {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $value = $this->sanitize($value);
            } elseif (is_string($key) && $this->isSensitiveField($key)) {
                $value = self::SANITIZED_VALUE;
            }
        }
        return $data;
    }

    /**
     * Checks if field is sensitive and should be sanitized
     *
     * @param string $fieldName
     * @return bool
     */
    private function isSensitiveField(string $fieldName): bool
    {
        if (in_array($fieldName, $this->fields)) {
            return true;
        }

        foreach ($this->regexFields as $regex) {
            if (preg_match($regex, $fieldName)) {
                return true;
            }
        }

        return false;
    }
}
