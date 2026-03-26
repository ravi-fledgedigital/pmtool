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
 * Menu sanitizer for input sanitization
 */
class MenuSanitizer
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
     * Checks if a menu item is sanitized.
     *
     * @param array $menuItems
     * @return array Returns an array of sanitized actions.
     */
    public function sanitizedMenuItems(array $menuItems): array
    {
        $sanitizedMenuItems = $this->sanitizer->sanitize($menuItems);
        return $this->sanitizeMenuItemsId($sanitizedMenuItems);
    }

    /**
     * Check id has the correct format accepted in Commerce
     *
     * @param array $menuItems
     * @return array
     */
    private function sanitizeMenuItemsId(array $menuItems): array
    {
        $sanitizedMenuItems = array_filter(
            $menuItems,
            fn($menuItem) => preg_match('/^[A-Za-z0-9\/:_]+$/', $menuItem['id']) === 1
        );
        if (count($menuItems) - count($sanitizedMenuItems) !== 0) {
            $this->logger->error(
                sprintf('One or more registered menu items failed due to wrong id format.')
            );
        }
        return array_values($sanitizedMenuItems);
    }
}
