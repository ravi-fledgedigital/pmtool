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

use Magento\Catalog\Model\Layer\Filter\Item;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Mirasvit\LayeredNavigation\Model\Config\HighlightConfigProvider;
use Mirasvit\LayeredNavigation\Model\Config\SeoConfigProvider;
use Mirasvit\LayeredNavigation\Model\Config\SizeLimiterConfigProvider;
use Mirasvit\LayeredNavigation\Model\Config\Source\FilterApplyingModeSource;
use Mirasvit\LayeredNavigation\Model\ConfigProvider;
use Mirasvit\LayeredNavigation\Service\FilterService;
use Magento\Swatches\Helper\Media as MediaHelper;
use Mirasvit\LayeredNavigation\Model\Config\Source\LinkRelSource;

class LabelRenderer extends AbstractRenderer
{
    protected $_template = 'Mirasvit_LayeredNavigation::renderer/labelRenderer.phtml';

    protected $configProvider;

    private $filterService;

    private $highlightConfigProvider;

    private $mediaHelper;

    private $sortedOptionsConfig;

    public function __construct(
        FilterService $filterService,
        ConfigProvider $configProvider,
        HighlightConfigProvider $highlightConfigProvider,
        MediaHelper $mediaHelper,
        SeoConfigProvider $seoConfigProvider,
        Template\Context $context,
        array $data = []
    ) {
        $this->filterService           = $filterService;
        $this->configProvider          = $configProvider;
        $this->highlightConfigProvider = $highlightConfigProvider;
        $this->mediaHelper             = $mediaHelper;

        parent::__construct($seoConfigProvider, $configProvider, $context, $data);
    }

    public function isFilterItemChecked(Item $filterItem, bool $multiselect = false): bool
    {
        return $this->filterService->isFilterItemChecked($filterItem, $multiselect);
    }

    public function isAjaxEnabled(): bool
    {
        return $this->configProvider->isAjaxEnabled();
    }

    public function getImageUrl(Item $filterItem): ?string
    {
        if ($optionConfig = $this->getSortedAttributeConfig()[$filterItem->getValueString()] ?? false) {
            if ($optionConfig->getImagePath()) {
                return $this->mediaHelper->getSwatchMediaUrl() . $optionConfig->getImagePath();
            }
        }

        return null;
    }

    public function isFullWidthImage(Item $filterItem): bool
    {
        if ($optionConfig = $this->getSortedAttributeConfig()[$filterItem->getValueString()] ?? false) {
            return $optionConfig->isFullImageWidth();
        }
        return false;
    }

    public function isHighlightEnabled(): bool
    {
        return $this->highlightConfigProvider->isEnabled($this->storeId);
    }

    public function getFilterItemDisplayMode(string $attributeCode = ''): string
    {
        return $this->configProvider->getFilterItemDisplayMode($attributeCode);
    }

    public function isApplyingMode(): bool
    {
        return $this->isAjaxEnabled() && $this->configProvider->getApplyingMode() == FilterApplyingModeSource::OPTION_BY_BUTTON_CLICK;
    }

    private function getSortedAttributeConfig()
    {
        if (!$this->sortedOptionsConfig) {
            $this->sortedOptionsConfig = array_reduce(
                $this->attributeConfig->getOptionsConfig(),
                function ($result, $item) {
                    $result[$item->getOptionId()] = $item;
                    return $result;
                },
                []
            );
        }
        return $this->sortedOptionsConfig;
    }

    public function getRelAttributeValue(): string
    {
        $relAttributeValue = parent::getRelAttributeValue();
        $nofollowFiltersThreshold = $this->seoConfigProvider->getNofollowFiltersThreshold();
        
        if ($nofollowFiltersThreshold != 0) {
            $relAttributeValue = count($this->filterService->getActiveFilters()) >= $nofollowFiltersThreshold 
                ? LinkRelSource::NO_FOLLOW 
                : $relAttributeValue;
        } 

        return $relAttributeValue;
    }


}
