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

namespace Magento\CommerceBackendUix\Model\Parser;

use Magento\CommerceBackendUix\Model\Sanitizer\MenuSanitizer;

/**
 * Menu item parser for registration
 */
class MenuParser implements ParserInterface
{
    /**
     * @param MenuSanitizer $menuSanitizer
     */
    public function __construct(private MenuSanitizer $menuSanitizer)
    {
    }

    /**
     * @inheritdoc
     */
    public function parse(array $loadedRegistrations, array &$parsedRegistrations, string $extensionId): void
    {
        if (!isset($loadedRegistrations['menuItems'])) {
            return;
        }
        $sanitizedMenuItems = $this->menuSanitizer->sanitizedMenuItems($loadedRegistrations['menuItems']);
        foreach ($sanitizedMenuItems as $menuItem) {
            $this->completeMenuItem($menuItem);
            if (isset($menuItem['isSection']) && $menuItem['isSection']) {
                $parsedRegistrations['menu']['sections'][] = $menuItem;
            } else {
                $menuItem['action'] = 'adminuisdk/menu/page/extensionId/' . $extensionId;
                $menuItem['page'] = $loadedRegistrations['page'] ?? [];
                $parsedRegistrations['menu']['items'][$extensionId] = $menuItem;
            }
        }
    }

    /**
     * Update menuItem to add missing mandatory fields
     *
     * @param array $menuItem
     * @return void
     */
    private function completeMenuItem(array &$menuItem): void
    {
        $menuItem['moduleName'] = 'Magento_CommerceBackendUix';
        $menuItem['resource'] = 'Magento_Backend::admin';
        $menuItem['module'] = 'Magento_CommerceBackendUix';
    }
}
