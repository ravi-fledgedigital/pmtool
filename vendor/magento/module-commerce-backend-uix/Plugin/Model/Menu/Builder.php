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

namespace Magento\CommerceBackendUix\Plugin\Model\Menu;

use Magento\Backend\Model\Menu;
use Magento\Backend\Model\Menu\Builder as BaseBuilder;
use Magento\Backend\Model\Menu\Item\Factory;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\CommerceBackendUix\Model\Config;

/**
 * Plugin class to append menus from registries
 */
class Builder
{
    /**
     * @param Factory $itemFactory
     * @param Config $config
     * @param Cache $cache
     */
    public function __construct(
        private Factory $itemFactory,
        private Config $config,
        private Cache $cache
    ) {
    }

    /**
     * After menu is loaded, complete with menu items from registries
     *
     * @param BaseBuilder $config
     * @param Menu $menu
     * @return Menu
     */
    public function afterGetResult(BaseBuilder $config, Menu $menu)
    {
        if (!$this->config->isAdminUISDKEnabled()) {
            return $menu;
        }

        $registrations = $this->cache->getRegistrations();
        if (!isset($registrations['menu']['items'], $registrations['menu']['sections'])) {
            return $menu;
        }
        $items = array_merge($registrations['menu']['sections'], $registrations['menu']['items']);
        foreach ($items as $menuItem) {
            $sortOrder = $menuItem['sortOrder'] ?? null;
            $parentId = $menuItem['parent'] ?? null;
            $menu->add($this->itemFactory->create($menuItem), $parentId, $sortOrder);
        }

        return $menu;
    }
}
