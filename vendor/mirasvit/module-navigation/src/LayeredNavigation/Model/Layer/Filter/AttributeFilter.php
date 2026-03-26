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

namespace Mirasvit\LayeredNavigation\Model\Layer\Filter;

use Magento\Catalog\Model\Layer;
use Magento\Framework\App\RequestInterface;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;
use Mirasvit\LayeredNavigation\Helper\ArrayHelper;
use Mirasvit\LayeredNavigation\Model\Config\SizeLimiterConfigProvider;
use Mirasvit\LayeredNavigation\Repository\AttributeConfigRepository;
use Mirasvit\LayeredNavigation\Repository\GroupRepository;
use Mirasvit\LayeredNavigation\Service\FilterService;

class AttributeFilter extends AbstractFilter
{
    private $attributeConfigRepository;

    private $groupRepository;

    private $sizeLimiterConfigProvider;

    private $filterService;

    public function __construct(
        AttributeConfigRepository $attributeConfigRepository,
        GroupRepository $groupRepository,
        SizeLimiterConfigProvider $sizeLimiterConfigProvider,
        Layer $layer,
        Context $context,
        FilterService $filterService,
        array $data = []
    ) {
        parent::__construct($layer, $context, $data);

        $this->attributeConfigRepository = $attributeConfigRepository;
        $this->groupRepository           = $groupRepository;
        $this->sizeLimiterConfigProvider = $sizeLimiterConfigProvider;
        $this->filterService             = $filterService;
    }

    public function apply(RequestInterface $request): self
    {
        $attributeValue = (string)$request->getParam($this->_requestVar);
        if (empty($attributeValue) && $attributeValue !== '0') {
            return $this;
        }

        $attributeValue = explode(',', (string)$attributeValue);

        // Validate and sanitize attribute values for dropdown/select types
        $attribute = $this->getAttributeModel();
        $attributeValue = $this->validateAttributeValues($attributeValue, $attribute);

        if ($attributeValue === null) {
            return $this;
        }

        // resolve grouped options
        $resolvedValue = $attributeValue;

        foreach ($resolvedValue as $value) {
            if ($group = $this->groupRepository->getByCode($value)) {
                $key = array_search($value, $resolvedValue);
                unset($resolvedValue[$key]);

                $resolvedValue = array_merge($resolvedValue, $group->getAttributeValueIds());
            }
        }

        $resolvedValue = array_values(array_unique($resolvedValue));

        $attribute = $this->getAttributeModel();

        // apply
        $this->getLayer()->getProductCollection()
            ->addFieldToFilter($attribute->getAttributeCode(), $resolvedValue);

        // add state
        if ($this->stateBarConfigProvider->isFilterClearBlockInOneRow()) {
            $labels = array_map(function ($value) {
                return $this->getOptionText($value);
            }, $attributeValue);

            $optionText = implode(', ', $labels);
            $this->addStateItem(
                $this->_createItem($optionText, $attributeValue)
            );
        } else {
            foreach ($attributeValue as $value) {
                $this->addStateItem(
                    $this->_createItem($this->getOptionText($value), $value)
                );
            }
        }

        $this->_items = null;

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _getItemsData(): array
    {
        $attribute = $this->getAttributeModel();
        $attributeCode = $attribute->getAttributeCode();

        /** @var \Mirasvit\LayeredNavigation\Model\ResourceModel\Fulltext\Collection $collection */
        $collection = $this->getLayer()->getProductCollection();

        $optionsFacetedData = $collection->getExtendedFacetedData(
            $attributeCode,
            $this->configProvider->isMultiselectEnabled($this->_requestVar)
        );

        $isAttributeFilterable = $this->getAttributeIsFilterable($attribute) === static::ATTRIBUTE_OPTIONS_ONLY_WITH_RESULTS;

        if (count($optionsFacetedData) === 0 && !$isAttributeFilterable) {
            return $this->itemDataBuilder->build();
        }

        $stateFilters = $this->getLayer()->getState()->getFilters();
        $stateAttributeCodes = [];

        foreach ($stateFilters as $filter) {
            if ($filter->getFilter()->getData('attribute_model')) {
                $stateAttributeCodes[] = $filter->getFilter()->getAttributeModel()->getAttributeCode();
            }
        }

        if (
            !$this->configProvider->isMultiselectEnabled($attributeCode)
            && in_array($attributeCode, $stateAttributeCodes)
        ) {
            return $this->itemDataBuilder->build();
        }

        $productSize = $collection->getSize();
        $options     = $attribute->getFrontend()->getSelectOptions();

        // Remove empty and zero-result options
        foreach ($options as $key => $option) {
            $value = (string)$this->getOptionValue($option);
            if (empty($value) && $value !== '0') {
                unset($options[$key]);
                continue;
            }

            if ($isAttributeFilterable && !$this->getOptionCount($value, $optionsFacetedData)) {
                unset($options[$key]);
            }
        }

        [$options, $presentGroups] = $this->resolveGroupedOptions($options, $attributeCode);

        if ($this->isSortByLabel() || $this->isSortByNaturalLabel()) {
            foreach ($presentGroups as $group) {
                $options[] = [
                    'label' => $group->getLabelByStoreId((int)$this->getStoreId()),
                    'value' => $group->getCode(),
                    'group' => $group->getId()
                ];
            }

            $comparator = $this->isSortByNaturalLabel() ? 'strnatcmp' : 'strcmp';
            usort($options, function ($a, $b) use ($comparator) {
                return $comparator($a['label'], $b['label']);
            });
        } elseif ($this->isSortByCounts()) {
            foreach ($presentGroups as $group) {
                $options[] = [
                    'label' => $group->getLabelByStoreId((int)$this->getStoreId()),
                    'value' => $group->getCode(),
                    'group' => $group->getId()
                ];
            }

            $options = $this->sortByCounts($options, $optionsFacetedData);
        } else {
            foreach ($presentGroups as $group) {
                $groupedOption = [
                    $group->getCode() => [
                        'label' => $group->getLabelByStoreId((int)$this->getStoreId()),
                        'value' => $group->getCode(),
                        'group' => $group->getId()
                    ]
                ];
                $options = ArrayHelper::insertIntoPosition($options, $groupedOption, $group->getPosition());
            }
        }

        foreach ($options as $option) {
            $value = (string)$this->getOptionValue($option);

            if (
                $this->configProvider->isHideUnusefulOptionEnabled()
                && $this->isMultiselectLogicAnd()
                && $this->getOptionCount($value, $optionsFacetedData) == $productSize
                && !in_array($value, $this->getActiveFilterOptions())
            ) {
                continue;
            }
            $this->buildOptionData($option, $isAttributeFilterable, $optionsFacetedData, $productSize);
        }
        return $this->itemDataBuilder->build();
    }

    private function resolveGroupedOptions(array $options, string $attributeCode): array
    {
        $presentGroups = [];

        $groupAtrributesList = $this->groupRepository->getAttributesList();

        if (!in_array($attributeCode, $groupAtrributesList)) {
            return [$options, $presentGroups];
        }

        $groups = $this->groupRepository->getGroupsListByAttributeCode($attributeCode);

        foreach ($groups as $group) {
            foreach ($options as $key => $option) {
                if (in_array((int)$option['value'], $group->getAttributeValueIds())) {
                    unset($options[$key]);
                    $presentGroups[$group->getCode()] = $group;
                }
            }
        }

        return [$options, $presentGroups];
    }

    protected function sortByCounts(array $options, array $optionsFacetedData): array
    {
        foreach ($options as $key => $option) {
            if (isset($option['group'])) {
                $options[$key]['count'] = $this->getGroupOptionCount((int)$option['group'], $optionsFacetedData);
            } else {
                $value = $option['value'] ?? null;
                $options[$key]['count'] = isset($optionsFacetedData[$value]['count']) ? (int)$optionsFacetedData[$value]['count'] : 0;
            }
        }

        usort($options, function ($a, $b) {
            return ($b['count'] ?? 0) <=> ($a['count'] ?? 0);
        });

        return $options;
    }

    private function getGroupOptionCount(int $groupId, array $optionsFacetedData): int
    {
        $group = $this->groupRepository->get($groupId);
        $count = 0;

        foreach ($group->getAttributeValueIds() as $optionId) {
            $count += $optionsFacetedData[$optionId]['count'] ?? 0;
        }

        return $count;
    }

    private function getActiveFilterOptions(): array
    {
        $activeOptions = [];

        $activeFilters = $this->filterService->getActiveFilters();

        foreach ($activeFilters as $filter) {
            $value = $filter->getValue();

            // exclude yes/no filters
            if ($value !== '0' && $value !== '1') {
                $activeOptions[] = $value;
            }
        }

        return $activeOptions;
    }

    private function isMultiselectLogicAnd(): bool
    {
        return $this->getAttributeConfig()->getMultiselectLogic() === AttributeConfigInterface::MULTISELECT_LOGIC_AND;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function buildOptionData(array $option, bool $isAttributeFilterable, array $optionsFacetedData, int $productSize): void
    {
        $value = (string)$this->getOptionValue($option);

        if (empty($value) && $value !== '0') {
            return;
        }

        if (isset($option['group'])) {
            $countMax = 0;
            $count = 0;
            $group = $this->groupRepository->get($option['group']);

            foreach ($group->getAttributeValueIds() as $optionValue) {
                $optionCount = $this->getOptionCount($optionValue, $optionsFacetedData);

                $countMax = $optionCount > $countMax
                    ? $optionCount
                    : $countMax;

                $count += $optionCount;
            }

            $count = $count >= $productSize ? $productSize : $count;
            $count = $count < $countMax ? $countMax : $count;
        } else {
            $count = $this->getOptionCount($value, $optionsFacetedData);
        }

        if ($isAttributeFilterable && $count === 0) {
            return;
        }

        $this->itemDataBuilder->addItemData(
            strip_tags((string)$option['label']),
            $value,
            $count
        );
    }

    private function validateAttributeValues(array $attributeValue, $attribute): ?array
    {
        $frontendInput = $attribute->getFrontendInput();

        if (!in_array($frontendInput, ['select', 'multiselect'])) {
            return $attributeValue;
        }

        $hasInvalidValue = false;

        $attributeValue = array_filter($attributeValue, function ($value) use (&$hasInvalidValue) {
            $value = trim($value);

            if (empty($value) && $value !== '0') {
                $hasInvalidValue = true;
                return false;
            }

            // Allow valid grouped options codes
            if ($this->groupRepository->getByCode($value)) {
                return true;
            }

            // Only allow numeric values for option IDs
            $isValid = is_numeric($value) && ctype_digit(ltrim($value, '-'));
            if (!$isValid) {
                $hasInvalidValue = true;
            }

            return $isValid;
        });

        // If no valid values exist, apply filter with non-existent value
        if (empty($attributeValue) && $hasInvalidValue) {
            return ['-9999999'];
        }

        if (!empty($attributeValue)) {
            return array_values($attributeValue);
        }

        return null;
    }

    private function getOptionValue(array $option): ?string
    {
        if ((empty($option['value']) && $option['value'] !== 0) || (!is_numeric($option['value']) && !isset($option['group']))) {
            return null;
        }

        return (string)$option['value'];
    }

    private function getOptionCount(string $value, array $optionsFacetedData): int
    {
        return isset($optionsFacetedData[$value]['count'])
            ? (int)$optionsFacetedData[$value]['count']
            : 0;
    }

    /**
     * Resolve state labels for grouped options
     *
     * @param int|string $value
     * @return string|bool
     */
    protected function getOptionText($value)
    {
        if ($group = $this->groupRepository->getByCode($value)) {
            return $group->getLabelByStoreId((int)$this->getStoreId());
        }

        return parent::getOptionText($value);
    }

    private function getAttributeConfig(): AttributeConfigInterface
    {
        $attrConfig = $this->attributeConfigRepository->getByAttributeCode(
            $this->getAttributeModel()->getAttributeCode()
        );

        return $attrConfig ? $attrConfig : $this->attributeConfigRepository->create();
    }

    public function isSortByLabel(): bool
    {
        return $this->getAttributeConfig()->getOptionsSortBy() === AttributeConfigInterface::OPTION_SORT_BY_LABEL;
    }

    public function isSortByNaturalLabel(): bool
    {
        return $this->getAttributeConfig()->getOptionsSortBy() === AttributeConfigInterface::OPTION_SORT_BY_NATURAL_LABEL;
    }

    public function isSortByCounts(): bool
    {
        return $this->getAttributeConfig()->getOptionsSortBy() === AttributeConfigInterface::OPTION_SORT_BY_COUNT;
    }

    public function isUseAlphabeticalIndex(): bool
    {
        return $this->getAttributeConfig()->getUseAlphabeticalIndex();
    }

    public function getAlphabeticalLimit(): int
    {
        return $this->sizeLimiterConfigProvider->getAlphabeticalLimit();
    }

    public function isAlphabeticalIndexAllowedByLimit(): bool
    {
        return $this->getAlphabeticalLimit() <= count($this->getItems());
    }
}
