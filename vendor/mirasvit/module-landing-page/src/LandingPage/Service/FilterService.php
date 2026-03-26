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
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



declare(strict_types=1);

namespace Mirasvit\LandingPage\Service;

use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Mirasvit\LandingPage\Repository\FilterRepository;
use Magento\Catalog\Model\CategoryRepository;

class FilterService
{
    private static $activeFilters = null;

    private $layerResolver;

    private $filterRepository;

    private $categoryRepository;

    public function __construct(
        LayerResolver $layerResolver,
        FilterRepository $filterRepository,
        CategoryRepository $categoryRepository
    ) {
        $this->layerResolver      = $layerResolver->get();
        $this->filterRepository   = $filterRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function getFiltersData(): array
    {
        $activeFilters = $this->getActiveFilters();
        $filtersData = [];

        foreach ($activeFilters as $key => $filter) {
            $values = (string)$filter->getValueString();
            $attributeCode = $filter->getFilter()->getRequestVar();
            if (isset($filtersData[$attributeCode])) {
                $filtersData[$attributeCode]['values'] = $this->prepareFilterValues($filtersData[$attributeCode]['values'], $values);
            } else {
                $filtersData[$attributeCode] = ['code' => $attributeCode, 'values' => $values];
            }
        }

        return $filtersData;
    }

    private function prepareFilterValues(string $oldValues, string $newValues): string
    {
        $currentValues = explode(',', $oldValues);
        $currentValues[] = $newValues;
        sort($currentValues, SORT_NUMERIC);

        return implode(',', $currentValues);
    }

    /** @return Item[] */
    private function getActiveFilters(): array
    {
        if (self::$activeFilters === null) {
            self::$activeFilters = $this->layerResolver->getState()->getFilters();
        }

        return (self::$activeFilters === null || !is_array(self::$activeFilters)) ? [] : self::$activeFilters;
    }

    public function validateFilters(PageInterface $landingPage, array $filtersData, $searchPage = false): bool
    {
        if ($searchPage) {
            $categoryId = isset($filtersData['category_ids']) ? $filtersData['category_ids']['values'] : '';

            if (!$this->validateCategoryFilter($landingPage, $categoryId)) {
                return false;
            }

            unset($filtersData['category_ids']);
        }

        $filterCollection = $this->filterRepository->getByPageId(intval($landingPage->getId()));

        if ($filterCollection->getSize() == count($filtersData)) {
            foreach ($filterCollection as $filter) {
                if (
                    !isset($filtersData[$filter->getAttributeCode()])
                    || $filtersData[$filter->getAttributeCode()]['values'] != $filter->getOptionIds()
                ) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    public function validateCategories(PageInterface $landingPage, $category): bool
    {
        $pageCategoryIds = explode(',', $landingPage->getCategories());
        if (count($pageCategoryIds) == 1 || $this->isCommonParentCategory($pageCategoryIds)) {
            return true;
        }
        return false;
    }

    private function isCommonParentCategory(array $ids): bool
    {
        $parentId = null;

        foreach ($ids as $id) {
            $category = $this->categoryRepository->get($id);
            if (!$parentId) {
                $parentId = $category->getParentId();
            }
            if ($parentId != $category->getParentId()) {
                return false;
            }
        }
        return true;
    }

    private function validateCategoryFilter(PageInterface $landingPage, string $categoryIds): bool
    {
        $pageCategoryIds = $landingPage->getCategories();

        if ($pageCategoryIds == $categoryIds) {
            return true;
        }

        $categoryId = explode(',', strval($categoryIds));
        $pageCategoryIds = explode(',', strval($pageCategoryIds));

        if (count($categoryId) === 1 && in_array($categoryId[0], $pageCategoryIds)) {
            return $this->isCommonParentCategory($pageCategoryIds);
        }
        return false;
    }
}
