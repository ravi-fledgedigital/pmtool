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
use Magento\CommerceBackendUix\Model\Sanitizer\InputSanitizer;

/**
 * Grid column sanitizer for input registrations
 */
class GridColumnSanitizer
{
    /**
     * @param LoggerHandler $logger
     * @param InputSanitizer $sanitizer
     */
    public function __construct(
        private LoggerHandler $logger,
        private InputSanitizer $sanitizer
    ) {
    }

    /**
     * Returns a sanitized list of product grid columns
     *
     * @param array $columns
     * @return array
     */
    public function sanitize(array $columns): array
    {
        if (empty($columns)) {
            return [];
        }

        if (!$this->areColumnsBaseParametersValid($columns)) {
            $this->logger->error(
                'Failed to register grid columns. Missing mandatory fields in registration.'
            );
            return [];
        }

        $columns['properties'] = $this->sanitizer->sanitize($columns['properties']);
        return $columns;
    }

    /**
     * Check if columns data is valid
     *
     * @param array $columns
     * @return bool
     */
    private function areColumnsBaseParametersValid(array $columns): bool
    {
        return ($columnsData = $columns['data'] ?? null) !== null
            && ($columnsData['meshId'] ?? null) !== null
            && ($columns['properties'] ?? null) !== null;
    }
}
