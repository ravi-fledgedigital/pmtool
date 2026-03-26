<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LayeredNavigation\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Mirasvit\LayeredNavigation\Repository\AttributeConfigRepository;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;

class HorizontalBarConfigProvider
{
    const STATE_BLOCK_NAME            = 'catalog.navigation.state';
    const STATE_SEARCH_BLOCK_NAME     = 'catalogsearch.navigation.state';
    const STATE_HORIZONTAL_BLOCK_NAME = 'm.catalog.navigation.horizontal.state';

    const POSITION_SIDEBAR    = 'sidebar';
    const POSITION_HORIZONTAL = 'horizontal';
    const POSITION_BOTH       = 'both';

    private ScopeConfigInterface $scopeConfig;
    private AttributeConfigRepository $attributeConfigRepository;

    private array $filterNavPositions = [];
    private bool $hasSidebarFilters = true;

    private $filtersCache = null;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        AttributeConfigRepository $attributeConfigRepository
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->attributeConfigRepository = $attributeConfigRepository;
    }

    public function getFilterPosition(string $attributeCode): string
    {
        $filters = $this->getFilters();

        foreach ([$attributeCode, '*'] as $filter) {
            if (isset($filters[$filter]) && isset($filters[$filter][AttributeConfigInterface::POSITION])) {
                return $filters[$filter][AttributeConfigInterface::POSITION];
            }
        }

        return self::POSITION_SIDEBAR;
    }

    public function getHideHorizontalFiltersValue(): int
    {
        return (int)$this->scopeConfig->getValue(
            'mst_nav/horizontal_bar/horizontal_filters_hide',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getFilters(): array
    {
        if ($this->filtersCache !== null) {
            return $this->filtersCache;
        }

        $filters = [];

        foreach ($this->attributeConfigRepository->getCollection() as $config) {
            $attributeCode = $config->getAttributeCode();

            if (!$attributeCode) {
                continue;
            }

            $configData = $config->getConfig();
            if (!is_array($configData) || empty($configData['filter_position'])) {
                continue;
            }

            $filters[$attributeCode][AttributeConfigInterface::POSITION] = $configData[AttributeConfigInterface::POSITION];

            if (isset($configData[AttributeConfigInterface::HORIZONTAL_POSITION])) {
                $filters[$attributeCode][AttributeConfigInterface::HORIZONTAL_POSITION] = $configData[AttributeConfigInterface::HORIZONTAL_POSITION];
            }
        }

        $this->filtersCache = $filters;

        return $filters;
    }

    public function setFilterNavPosition(string $filter, string $position): void
    {
        $this->filterNavPositions[$filter] = $position;
    }

    public function getFilterNavPositions(): array
    {
        return $this->filterNavPositions;
    }

    public function getHasSidebarFilters(): bool
    {
        return $this->hasSidebarFilters;
    }

    public function setHasSidebarFilters(bool $value): void
    {
        $this->hasSidebarFilters = $value;
    }
}
