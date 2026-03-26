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

namespace Mirasvit\LayeredNavigation\Model\Layer;

use Magento\Catalog\Model\Config\LayerCategoryConfig;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Catalog\Model\Layer\FilterableAttributeListInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;
use Mirasvit\LayeredNavigation\Model\Config\ExtraFilterConfigProvider;
use Mirasvit\LayeredNavigation\Model\Config\HorizontalBarConfigProvider;
use Mirasvit\LayeredNavigation\Model\ConfigProvider;
use Mirasvit\LayeredNavigation\Model\Layer\Filter\SearchFilter;
use Mirasvit\LayeredNavigation\Repository\AttributeConfigRepository;

/**
 * @SuppressWarnings(PHPMD)
 */
class FilterList extends Layer\FilterList
{
    protected $filterTypes
        = [
            self::CATEGORY_FILTER  => Filter\CategoryFilter::class,
            self::ATTRIBUTE_FILTER => Filter\AttributeFilter::class,
            self::PRICE_FILTER     => Filter\DecimalFilter::class,
            self::DECIMAL_FILTER   => Filter\DecimalFilter::class,
        ];

    private   $isHorizontal;

    private   $additionalFilters;

    private   $attributeConfigRepository;

    private   $extraFilterConfigProvider;

    private   $horizontalBarConfigProvider;

    private   $configProvider;

    public function __construct(
        ObjectManagerInterface $objectManager,
        FilterableAttributeListInterface $filterableAttributes,
        ExtraFilterConfigProvider $extraFilterConfigProvider,
        HorizontalBarConfigProvider $horizontalBarConfigProvider,
        AttributeConfigRepository $attributeConfigRepository,
        ConfigProvider $configProvider,
        LayerCategoryConfig $layerCategoryConfig,
        bool $isHorizontal = false,
        array $filters = [],
        array $additionalFilters = []
    ) {
        $this->isHorizontal                = $isHorizontal;
        $this->extraFilterConfigProvider   = $extraFilterConfigProvider;
        $this->horizontalBarConfigProvider = $horizontalBarConfigProvider;
        $this->attributeConfigRepository   = $attributeConfigRepository;
        $this->configProvider              = $configProvider;
        $this->additionalFilters           = $additionalFilters;

        parent::__construct($objectManager, $filterableAttributes, $layerCategoryConfig, $filters);
    }

    /**
     * Retrieve list of filters
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param Layer $layer
     *
     * @return array|AbstractFilter[]
     */
    public function getFilters(Layer $layer): array
    {
        if (!count($this->filters)) {
            $this->filters = [];

            if ($this->configProvider->isCategoryFilterVisibleInLayerNavigation()) {
                $this->filters[] = $this->objectManager->create($this->filterTypes[self::CATEGORY_FILTER], ['layer' => $layer]);
            }

            foreach ($this->filterableAttributes->getList() as $attribute) {
                $this->filters[] = $this->createAttributeFilter($attribute, $layer);
            }

            foreach ($this->filters as $key => $filter) {
                $attribute = $filter->getData('attribute_model');

                if (!$attribute) {
                    continue;
                }

                $attributeConfig = $this->attributeConfigRepository->getByAttributeCode($attribute->getAttributeCode());

                if (!$attributeConfig) {
                    continue;
                }

                if ($layer instanceof \Magento\Catalog\Model\Layer\Search) {
                    if ((bool) $attributeConfig->getSearchVisibilityMode()) {
                        unset($this->filters[$key]);
                    }
                }

                if ($attributeConfig->getCategoryVisibilityMode() == AttributeConfigInterface::CATEGORY_VISIBILITY_MODE_SHOW_IN_SELECTED
                    && !in_array($layer->getCurrentCategory()->getId(), $attributeConfig->getCategoryVisibilityIds())) {
                    unset($this->filters[$key]);
                }

                if ($attributeConfig->getCategoryVisibilityMode() == AttributeConfigInterface::CATEGORY_VISIBILITY_MODE_HIDE_IN_SELECTED
                    && in_array($layer->getCurrentCategory()->getId(), $attributeConfig->getCategoryVisibilityIds())) {
                    unset($this->filters[$key]);
                }
            }

            $this->applyFilterPosition($this->getAdditionalFilters($layer));

            $this->handleFilterPositionInNavBar();

            if ($this->isHorizontal) {
                $this->sortFiltersByHorizontalPosition();
            }
        }

        return $this->filters;
    }

    private function sortFiltersByHorizontalPosition(): void
    {
        $filterWithPosition = [];

        foreach ($this->filters as $filter) {
            $attribute = $filter->getData('attribute_model');
            if (!$attribute) {
                $position = PHP_INT_MAX;
            } else {
                $attributeConfig = $this->attributeConfigRepository->getByAttributeCode($attribute->getAttributeCode());
                $position = $attributeConfig ? $attributeConfig->getHorizontalPosition() : PHP_INT_MAX;
            }

            $filterWithPosition[] = [
                'filter' => $filter,
                'position' => $position,
            ];
        }

        usort($filterWithPosition, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        $this->filters = array_column($filterWithPosition, 'filter');
    }

    private function applyFilterPosition(array $additionalFilters): void
    {
        foreach ($additionalFilters as $data) {
            foreach ($data as $position => $additionalFilter) {
                if (isset($this->filters[$position]) && $position != 0) {
                    $firstFilterPart  = array_slice($this->filters, 0, $position);
                    $secondFilterPart = array_slice($this->filters, $position);
                    $this->filters    = array_merge($firstFilterPart, [$additionalFilter], $secondFilterPart);
                } elseif ($position == 0) {
                    array_unshift($this->filters, $additionalFilter);
                } else {
                    $this->filters = array_merge($this->filters, [$additionalFilter]);
                }
            }
        }
    }

    /** @return AbstractFilter[] */
    private function getAdditionalFilters(Layer $layer): array
    {
        $additionalFilters = [];

        foreach ($this->additionalFilters as $filter => $class) {


            if ($this->extraFilterConfigProvider->isFilterEnabled($filter)) {
                $position = $this->extraFilterConfigProvider->getFilterPosition($filter);

                $additionalFilters[] = [
                    $position => $this->objectManager->create($class, ['layer' => $layer]),
                ];
            }
        }

        return $additionalFilters;
    }

    private function handleFilterPositionInNavBar(): void
    {
        $sidebarFiltersCount = 0;
        $searchFilterIdx     = null;

        foreach ($this->filters as $key => $filter) {
            if ($this->isSearchFilter($filter)) {
                $request = $this->objectManager->get(RequestInterface::class);

                if (
                    strpos($request->getFullActionName(), 'catalogsearch') !== false
                    || $request->getParam('amp')
                ) {
                    // 1. search results with custom search modules can conflicts with search filter
                    // 2. amp pages uses their own filter renderer
                    // So we remove search filter in those 2 cases to prevent errors on store front
                    unset($this->filters[$key]);
                    continue;
                }

                $searchFilterIdx = $key;
                continue; // skip search filter as we don't know other filters positions yet
            }

            $filterPosition = $this->horizontalBarConfigProvider->getFilterPosition($filter->getRequestVar());

            if ($filterPosition !== HorizontalBarConfigProvider::POSITION_HORIZONTAL) {
                $sidebarFiltersCount++;
            }

            $this->horizontalBarConfigProvider->setFilterNavPosition($filter->getRequestVar(), $filterPosition);

            if (
                ($this->isHorizontal && $filterPosition == HorizontalBarConfigProvider::POSITION_SIDEBAR)
                || (!$this->isHorizontal && $filterPosition == HorizontalBarConfigProvider::POSITION_HORIZONTAL)
            ) {
                unset($this->filters[$key]);
            }
        }

        // The preferable position for the search filter is the sidebar
        // Only if all filters are positioned in the horizontal nav bar
        // the search filter displayed in the horizontal nav bar as well
        if (!is_null($searchFilterIdx)) {
            if (
                ($this->isHorizontal && $sidebarFiltersCount > 0)
                || (!$this->isHorizontal && $sidebarFiltersCount == 0)
            ) {
                unset($this->filters[$searchFilterIdx]);
            }
        }

        $this->horizontalBarConfigProvider->setHasSidebarFilters($sidebarFiltersCount > 0);
    }

    private function isSearchFilter($filter): bool
    {
        return $filter instanceof SearchFilter;
    }
}
