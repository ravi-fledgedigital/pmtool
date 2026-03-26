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

namespace Mirasvit\LayeredNavigation\Block\Renderer;

use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Catalog\Model\ResourceModel\Layer\Filter\AttributeFactory;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Swatches\Block\LayeredNavigation\RenderLayered;
use Magento\Swatches\Helper\Data as SwatchesHelperData;
use Magento\Swatches\Helper\Media as SwatchesHelperMedia;
use Magento\Theme\Block\Html\Pager;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;
use Mirasvit\LayeredNavigation\Api\Data\GroupInterface;
use Mirasvit\LayeredNavigation\Helper\ArrayHelper;
use Mirasvit\LayeredNavigation\Model\Config\SeoConfigProvider;
use Mirasvit\LayeredNavigation\Model\Config\Source\FilterApplyingModeSource;
use Mirasvit\LayeredNavigation\Model\ConfigProvider;
use Mirasvit\LayeredNavigation\Repository\AttributeConfigRepository;
use Mirasvit\LayeredNavigation\Repository\GroupRepository;
use Mirasvit\LayeredNavigation\Service\FilterService;
use Magento\Framework\UrlInterface;
use Magento\Swatches\Model\Swatch;
use Mirasvit\LayeredNavigation\Model\Config\Source\LinkRelSource;

/**
 * Preference (di.xml) for @see \Magento\Swatches\Block\LayeredNavigation\RenderLayered
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SwatchRenderer extends RenderLayered
{
    protected $_template = 'Mirasvit_LayeredNavigation::renderer/swatchRenderer.phtml';

    protected $configProvider;

    private $attributeConfigRepository;

    private $filterService;

    /** @var AttributeConfigInterface */
    private $attributeConfig;

    private $groupRepository;

    private $htmlPagerBlock;

    private $seoConfigProvider;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        SeoConfigProvider $seoConfigProvider,
        ConfigProvider $configProvider,
        FilterService $filterService,
        AttributeConfigRepository $attributeConfigRepository,
        GroupRepository $groupRepository,
        Context $context,
        Attribute $eavAttribute,
        AttributeFactory $layerAttribute,
        SwatchesHelperData $swatchHelper,
        SwatchesHelperMedia $mediaHelper,
        ?Pager $htmlPagerBlock = null,
        array $data = []
    ) {
        $this->seoConfigProvider         = $seoConfigProvider;
        $this->configProvider            = $configProvider;
        $this->filterService             = $filterService;
        $this->attributeConfigRepository = $attributeConfigRepository;
        $this->groupRepository           = $groupRepository;
        $this->htmlPagerBlock            = $htmlPagerBlock ?? ObjectManager::getInstance()->get(Pager::class);

        parent::__construct(
            $context,
            $eavAttribute,
            $layerAttribute,
            $swatchHelper,
            $mediaHelper,
            $data
        );
    }

    public function setSwatchFilter(AbstractFilter $filter): self
    {
        $this->attributeConfig = $this->attributeConfigRepository->getByAttributeCode($filter->getRequestVar());

        return parent::setSwatchFilter($filter);
    }

    public function getDisplayMode(): string
    {
        return $this->attributeConfig->getDisplayMode();
    }

    public function getSwatchFilter(): AbstractFilter
    {
        return $this->filter;
    }

    public function getFilterUniqueValue(AbstractFilter $filter): string
    {
        return $this->filterService->getFilterUniqueValue($filter);
    }

    public function getFilterRequestVar(): string
    {
        $filter = $this->getSwatchFilter();
        if (!is_object($filter)) {
            return '';
        }

        return $filter->getRequestVar();
    }

    public function isItemChecked(string $option): bool
    {
        return $this->filterService->isFilterCheckedSwatch($this->filter->getRequestVar(), $option);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getSwatchData(): array
    {
        $swatchData      = parent::getSwatchData();
        $attributeConfig = $this->attributeConfig
            ?? $this->attributeConfigRepository->getByAttributeCode($swatchData['attribute_code']);

        if ($attributeConfig) {
            $attributeConfig = $attributeConfig->getConfig();
            $options = isset($attributeConfig['options']) ? $attributeConfig['options'] : [];
            unset($attributeConfig['options']);
            $swatchData      = array_merge($attributeConfig, $swatchData);

            foreach ($options as $option) {
                $optionId = $option['option_id'];
                if (isset($option['image_path']) && $option['image_path']) {
                    $option['value'] = $option['image_path'];
                    $swatchData['swatches'][$optionId]['value'] = $option['value'];
                    $swatchData['swatches'][$optionId]['type'] = Swatch::SWATCH_TYPE_VISUAL_IMAGE;
                }

                $option = array_filter($option);
                if (isset($swatchData['options'][$optionId])) {
                    $swatchData['options'][$optionId] = array_merge($swatchData['options'][$optionId], $option);
                }
            }
        }

        $resolvedSwatches = $this->resolveGroupedOptionSwatches($swatchData);

        if (
            isset($resolvedSwatches['options_sort_by'])
            && $resolvedSwatches['options_sort_by'] === AttributeConfigInterface::DISPLAY_MODE_LABEL
        ) {
            $this->sortOptions($resolvedSwatches);
        }

        if (
            isset($resolvedSwatches['options_sort_by'])
            && $resolvedSwatches['options_sort_by'] === AttributeConfigInterface::OPTION_SORT_BY_COUNT
        ) {
            $resolvedSwatches = $this->sortByCount($resolvedSwatches);
        }

        return $resolvedSwatches;
    }

    private function sortByCount(array $resolvedSwatches): array
    {
        $facetedData = $this->getSwatchFilter()->getLayer()->getProductCollection()->getExtendedFacetedData(
            $resolvedSwatches['attribute_code'],
            $this->configProvider->isMultiselectEnabled($resolvedSwatches['attribute_code'])
        );

        $fields = ['options', 'swatches'];

        foreach ($fields as $field) {
            foreach ($resolvedSwatches[$field] as $key => $option) {
                $resolvedSwatches[$field][$key]['count'] = $facetedData[$option['option_id']]['count'];
            }

            uasort($resolvedSwatches[$field], function ($a, $b) {
                $a = isset($a['count']) ? strval($a['count']) : '0';
                $b = isset($b['count']) ? strval($b['count']) : '0';
                if ($a < $b) {
                    return 1;
                } else if ($a > $b) {
                    return -1;
                }
                return 0;
            });
        }

        return $resolvedSwatches;
    }

    public function getRemoveUrl(string $attributeCode, string $optionId): string
    {
        return $this->buildUrl($attributeCode, $optionId);
    }

    public function getSwatchOptionLink(string $attributeCode, string $optionId): string
    {
        return $this->buildUrl($attributeCode, $optionId);
    }

    public function isApplyingMode(): bool
    {
        return $this->configProvider->isAjaxEnabled()
            && $this->configProvider->getApplyingMode() == FilterApplyingModeSource::OPTION_BY_BUTTON_CLICK;
    }

    private function resolveGroupedOptionSwatches(array $swatchData): array
    {
        $groups = $this->groupRepository->getGroupsListByAttributeCode($swatchData['attribute_code']);

        $options  = $swatchData['options'];
        $swatches = $swatchData['swatches'];

        foreach ($groups as $group) {
            foreach ($group->getAttributeValueIds() as $valueId) {
                unset($options[$valueId]);
                unset($swatches[$valueId]);
            }

            if (!$this->getProductCountByGroup($group)) {
                continue; // do not add grouped swatch filter if related options wasn't present
            }

            $groupedOption = [
                $group->getCode() => [
                    'label'        => $group->getLabelByStoreId((int)$this->_storeManager->getStore()->getId()),
                    'link'         => $this->buildUrl($swatchData['attribute_code'], $group->getCode()),
                    'custom_style' => ''
                ]
            ];

            $options = ArrayHelper::insertIntoPosition($options, $groupedOption, $group->getPosition());

            $groupedSwatch = [
                $group->getCode() => [
                    'swatch_id' => $group->getCode(),
                    'option_id' => $group->getCode(),
                    'store_id'  => 0,
                    'type'      => $group->getSwatchType(),
                    'value'     => $this->prepareSwatchGroupValue($group),
                    'grouped'   => true
                ]
            ];

            $swatches = ArrayHelper::insertIntoPosition($swatches, $groupedSwatch, $group->getPosition());
        }

        $swatchData['options']  = $options;
        $swatchData['swatches'] = $swatches;

        return $swatchData;
    }

    private function getProductCountByGroup(GroupInterface $group): int
    {
        $facetedData = $this->getSwatchFilter()->getLayer()->getProductCollection()->getExtendedFacetedData(
            $group->getAttributeCode(),
            $this->configProvider->isMultiselectEnabled($group->getAttributeCode())
        );

        $count = 0;

        foreach ($group->getAttributeValueIds() as $valueId) {
            $count += isset($facetedData[$valueId]['count']) ? (int)$facetedData[$valueId]['count'] : 0;
        }

        return $count;
    }

    private function prepareSwatchGroupValue(GroupInterface $group): ?string
    {
        switch ($group->getSwatchType()) {
            case GroupInterface::SWATCH_TYPE_COLOR:
                return $group->getSwatchValue();
            case GroupInterface::SWATCH_TYPE_IMAGE:
                return $this->configProvider->getMediaUrl($group->getSwatchValue());
            default:
                return $group->getSwatchValue() ?: $group->getLabelByStoreId((int)$this->_storeManager->getStore()->getId());
        }
    }

    public function getSwatchFilePath(string $type, ?string $filename, bool $grouped = false): string
    {
        if ($grouped) {
            return $filename;
        }

        return $this->getSwatchPath($type, $filename);
    }

    /**
     * @param string $attributeCode
     * @param int|string $optionId
     *
     * @return string
     */
    public function buildUrl($attributeCode, $optionId)
    {
        $query = $this->getRequest()->getParams();

        $activeFilterParams = $this->getActiveFilterParams();
        foreach ($activeFilterParams as $filterCode => $filterValue) {
            $query[$filterCode] = $filterValue;
        }

        $attrParams = isset($query[$attributeCode])
            ? explode(',', urldecode((string)$query[$attributeCode]))
            : [];

        if (isset($query['id'])) {
            unset($query['id']);
        }

        if (!in_array($optionId, $attrParams)) {
            if (!$this->configProvider->isMultiselectEnabled($attributeCode)) {
                $attrParams = [$optionId];
            } else {
                $attrParams[] = $optionId;
            }
        } else {
            $key = array_search($optionId, $attrParams);
            unset($attrParams[$key]);
        }

        $query[$attributeCode] = count($attrParams) ? implode(',', $attrParams) : null;

        $query[$this->htmlPagerBlock->getPageVarName()] = null;

        return $this->_urlBuilder->getUrl(
            '*/*/*',
            [
                '_current' => true,
                '_use_rewrite' => true,
                '_query' => $query
            ]
        );
    }

    private function getActiveFilterParams(): array
    {
        $result = [];
        $activeFilters = $this->filterService->getActiveFilters();

        foreach ($activeFilters as $filter) {
            $value = $filter->getData('value');
            $values = is_array($value) ? $value : explode(',', (string)$value);
            $requestVar = $filter->getFilter()->getRequestVar();

            $result[$requestVar] = implode(',', $values);
        }

        return $result;
    }

    public function getRelAttributeValue(): string
    {
        $relAttributeValue = $this->seoConfigProvider->getRelAttribute();
        $nofollowFiltersThreshold = $this->seoConfigProvider->getNofollowFiltersThreshold();

        if ($nofollowFiltersThreshold != 0) {
            $relAttributeValue = count($this->filterService->getActiveFilters()) >= $nofollowFiltersThreshold
                ? LinkRelSource::NO_FOLLOW
                : $relAttributeValue;
        }

        return $relAttributeValue;
    }

    public function getTooltip(): string
    {
        return $this->attributeConfig ? $this->attributeConfig->getTooltip() : '';
    }

    public function getAttributeClearUrl($attributeCode)
    {
        return $this->getUrl('*/*/*', [
                '_current' => true,
                '_use_rewrite' => true,
                '_query' => [$attributeCode => null],
                '_escape' => true,
            ]
        );
    }

    public function getFirstLetter(string $label): string
    {
        $letter = strtoupper(mb_substr($label, 0, 1));

        return preg_match('/^[\p{Latin}\p{Cyrillic}\x{0590}-\x{05FF}]+$/u', $letter) ? $letter : '#';
    }

    private function sortOptions(array &$resolvedSwatches): void
    {
        if ($resolvedSwatches['options'] && $resolvedSwatches['swatches']) {

            uasort($resolvedSwatches['options'], function ($a, $b) {
                if($a['label'] && $b['label']) {
                    return strnatcmp($a['label'], $b['label']);
                }
                return 0;
            });

            uasort($resolvedSwatches['swatches'], function ($a, $b) {
                if($a['value'] && $b['value']) {
                    return strnatcmp($a['value'], $b['value']);
                }
                return 0;
            });
        }
    }

    public function getSearchBoxElement(string $filterAccessor): string
    {
        /** @var Element\SearchBoxElement $block */
        $block = $this->_layout->createBlock(Element\SearchBoxElement::class);
        $block->setFilter($this->filter)
            ->setAttributeConfig($this->attributeConfig)
            ->setFilterAccessor($filterAccessor)
            ->setTemplate('Mirasvit_LayeredNavigation::renderer/element/searchBox.phtml');

        return $block->toHtml();
    }

    public function isMultiselectEnabled(): bool
    {
        $isMultiselect = $this->configProvider->isMultiselectEnabled();

        return is_null($isMultiselect) ? $this->configProvider->isMultiselectEnabled() : $isMultiselect;
    }
}
