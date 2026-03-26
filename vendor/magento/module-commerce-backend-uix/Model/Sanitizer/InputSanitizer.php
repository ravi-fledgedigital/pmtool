<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Model\Sanitizer;

use Magento\CommerceBackendUix\Model\Logs\LoggerHandler;

/**
 * Class used to sanitize input of registrations
 */
class InputSanitizer
{
    /**
     * @param LoggerHandler $logger
     * @param string $extensionPointNamespace
     * @param array $requiredFields
     * @param array $allowedValuesByField
     */
    public function __construct(
        private LoggerHandler $logger,
        private string $extensionPointNamespace,
        private array $requiredFields,
        private array $allowedValuesByField = []
    ) {
    }

    /**
     * Takes an input and returns a sanitized array of correct registrations
     *
     * @param array $input
     * @return array
     */
    public function sanitize(array $input): array
    {
        return $this->sanitizeInputAllowedValues($this->sanitizeMandatoryFields($input));
    }

    /**
     * Sanitize input checking mandatory fields
     *
     * @param array $input
     * @return array
     */
    private function sanitizeMandatoryFields(array $input): array
    {
        $sanitizedInput = array_filter(
            $input,
            fn($registration) => !$this->areMandatoryFieldsMissing($registration)
        );

        if (count($input) - count($sanitizedInput) !== 0) {
            $this->logger->error(sprintf(
                'One or more registered %s failed due to missing mandatory fields. Mandatory fields [%s]',
                $this->extensionPointNamespace,
                implode(',', $this->requiredFields)
            ));
        }
        return array_values($sanitizedInput);
    }

    /**
     * Sanitize input checking allowed values
     *
     * @param array $input
     * @return array
     */
    private function sanitizeInputAllowedValues(array $input): array
    {
        $sanitizedInput = array_filter($input, fn($registration) => $this->areFieldsValuesAllowed($registration));
        if (count($input) - count($sanitizedInput) !== 0) {
            $this->logger->error(sprintf(
                'One or more registered %s failed due to forbidden values for some fields.',
                $this->extensionPointNamespace
            ));
        }
        return array_values($sanitizedInput);
    }

    /**
     * Check if valued are allowed for configured fields
     *
     * @param array $data
     * @return bool
     */
    private function areFieldsValuesAllowed(array $data): bool
    {
        foreach ($data as $key => $item) {
            if (array_key_exists($key, $this->allowedValuesByField)
                && !in_array((string)$item, $this->allowedValuesByField[$key])
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if mandatory fields are missing
     *
     * @param array $data
     * @return bool
     */
    private function areMandatoryFieldsMissing(array $data): bool
    {
        foreach ($this->requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                return true;
            }
        }
        return false;
    }
}
