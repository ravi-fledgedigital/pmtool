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

namespace Mirasvit\LayeredNavigation\Block;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mirasvit\LayeredNavigation\Model\Config\HighlightConfigProvider;
use Mirasvit\LayeredNavigation\Model\Config\HorizontalBarConfigProvider;
use Mirasvit\LayeredNavigation\Model\Config\Source\FilterApplyingModeSource;
use Mirasvit\LayeredNavigation\Model\Config\StateBarConfigProvider;
use Mirasvit\LayeredNavigation\Model\ConfigProvider;
use Mirasvit\LayeredNavigation\Service\FilterService;
use Mirasvit\LayeredNavigation\Model\Config\SeoConfigProvider;
use Mirasvit\LayeredNavigation\Model\Config\ExtraFilterConfigProvider;

class Ajax extends Template
{
    private $filterService;

    private $configProvider;

    private $stateBarConfigProvider;

    private $horizontalBarConfigProvider;

    private $storeId;

    private $highlightConfigProvider;

    private $seoConfigProvider;

    private $extraFilterConfigProvider;

    public function __construct(
        Context $context,
        FilterService $filterService,
        ConfigProvider $configProvider,
        SeoConfigProvider $seoConfigProvider,
        HighlightConfigProvider $highlightConfigProvider,
        StateBarConfigProvider $stateBarConfigProvider,
        HorizontalBarConfigProvider $horizontalBarConfigProvider,
        ExtraFilterConfigProvider $extraFilterConfigProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->filterService               = $filterService;
        $this->configProvider              = $configProvider;
        $this->seoConfigProvider           = $seoConfigProvider;
        $this->stateBarConfigProvider      = $stateBarConfigProvider;
        $this->horizontalBarConfigProvider = $horizontalBarConfigProvider;
        $this->highlightConfigProvider     = $highlightConfigProvider;
        $this->extraFilterConfigProvider   = $extraFilterConfigProvider;
        $this->context                     = $context;
        $this->storeId                     = (int)$context->getStoreManager()->getStore()->getStoreId();
    }

    public function getJsonConfig(): array
    {
        return [
            '*' => [
                'Mirasvit_LayeredNavigation/js/ajax' => [
                    'cleanUrl'                   => $this->getCleanUrl(),
                    'overlayUrl'                 => $this->getOverlayUrl(),
                    'isSeoFilterEnabled'         => $this->isSeoFilterEnabled(),
                    'isFilterClearBlockInOneRow' => $this->isFilterClearBlockInOneRow(),
                ],
            ],
        ];
    }

    public function isAjaxEnabled(): bool
    {
        return $this->configProvider->isAjaxEnabled();
    }

    public function isInstantMode(): bool
    {
        return $this->configProvider->isAjaxEnabled()
            && $this->configProvider->getApplyingMode() == FilterApplyingModeSource::OPTION_INSTANTLY;
    }

    public function isConfirmationMode(): bool
    {
        return $this->configProvider->isAjaxEnabled()
            && $this->configProvider->getApplyingMode() == FilterApplyingModeSource::OPTION_BY_BUTTON_CLICK;
    }

    public function isConfirmOnMobile(): bool
    {
        return $this->isInstantMode()
            && $this->configProvider->getIsConfirmOnMobile();
    }

    public function getModeSwitchBreakpoint(): int
    {
        return $this->configProvider->getBreakpointForModeSwitch();
    }

    public function getFriendlyClearUrl(): string
    {
        return (string)ObjectManager::getInstance()->get('\Mirasvit\SeoFilter\Service\FriendlyUrlService')
            ->getClearUrl();
    }

    public function isSeoFilterEnabled(): bool
    {
        return $this->configProvider->isSeoFiltersEnabled();
    }

    public function isHighlightEnabled(): bool
    {
        return $this->highlightConfigProvider->isEnabled($this->storeId);
    }

    public function isPreCalculationEnabled(): bool
    {
        return $this->configProvider->isPreCalculationEnabled();
    }

    public function isShowClearButton(): bool
    {
        return $this->configProvider->isShowClearButton();
    }

    private function getCleanUrl(): string
    {
        $activeFilters = [];

        foreach ($this->filterService->getActiveFilters() as $item) {
            $filter = $item->getFilter();

            $activeFilters[$filter->getRequestVar()] = $filter->getCleanValue();
        }

        $params['_current']     = true;
        $params['_use_rewrite'] = true;
        $params['_query']       = $activeFilters;
        $params['_escape']      = true;

        $url = $this->_urlBuilder->getUrl('*/*/*', $params);
        $url = str_replace('&amp;', '&', $url);

        return $url;
    }


    private function getOverlayUrl(): string
    {
        return $this->getViewFileUrl('Mirasvit_LayeredNavigation::images/ajax_loading.gif');
    }

    private function isFilterClearBlockInOneRow(): bool
    {
        return $this->stateBarConfigProvider->isFilterClearBlockInOneRow();
    }

    public function getFiltersNavPositions(): array
    {
        return $this->horizontalBarConfigProvider->getFilterNavPositions();
    }

    public function isScrollToTopEnabled()
    {
        return $this->configProvider->getScrollToTopOption();
    }

    public function getScrollToTopBehaviour(): int
    {
        return $this->configProvider->getScrollToTopOption();
    }

    public function isStickySidebarEnabled(): bool
    {
        return $this->configProvider->isStickySidebarEnabled();
    }

    public function getRelAttributeValue(): string
    {
        return $this->seoConfigProvider->getRelAttribute();
    }

    public function isCategoryFilterModeLink(): bool
    {
        $isCategoryPage = $this->context->getRequest()->getFullActionName() === 'catalog_category_view';

        return $isCategoryPage && $this->extraFilterConfigProvider->isCategoryFilterModeLink();
    }
}
