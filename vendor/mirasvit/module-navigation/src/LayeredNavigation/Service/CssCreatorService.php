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

namespace Mirasvit\LayeredNavigation\Service;

use Magento\Framework\App\ObjectManager;
use Mirasvit\LayeredNavigation\Model\Config;
use Mirasvit\LayeredNavigation\Model\ConfigProvider;

class CssCreatorService
{
    private $horizontalFiltersConfig;

    private $highlightConfigProvider;

    private $configProvider;

    private $stateBarConfigProvider;

    public function __construct(
        ConfigProvider $configProvider,
        Config\HorizontalBarConfigProvider $horizontalFiltersConfig,
        Config\HighlightConfigProvider $highlightConfigProvider,
        Config\StateBarConfigProvider $stateBarConfigProvider
    ) {
        $this->configProvider          = $configProvider;
        $this->horizontalFiltersConfig = $horizontalFiltersConfig;
        $this->highlightConfigProvider = $highlightConfigProvider;
        $this->stateBarConfigProvider  = $stateBarConfigProvider;
    }

    public function getCssContent(int $storeId): string
    {
        $css = '';
        $css = $this->getHorizontalFiltersCss($storeId, $css);
        $css = $this->getHighlightColorCss($storeId, $css);
        $css = $this->getFilterClearBlockCss($storeId, $css);

        $css = $this->getDisplayOptionsCss($storeId, $css);
        $css = $this->getShowOpenedFiltersCss($storeId, $css);
        $css = $this->getDisplayFilterCss($storeId, $css);

        $css .= $this->configProvider->getAdditionalCss();

        return $css;
    }

    private function getDisplayFilterCss(int $storeId, string $css): string
    {
        $baseFilterSelector  = '.filter-options .filter-options-item';

        $css .= '/* Display filters rules - begin */';

        if (!$this->horizontalFiltersConfig->getHasSidebarFilters()) {
            $css .= ".sidebar .block.filter .filter-options {display:none;}";
            $css .= ".sidebar .block.filter strong[role=heading] {display:none;}";
        }

        $css .= ".sidebar {$baseFilterSelector}[data-nav-position=horizontal] {display:none;}";

        if ($hideHorizontalFiltersValue = $this->horizontalFiltersConfig->getHideHorizontalFiltersValue()) {
            $css .= '@media all and (max-width: ' . $hideHorizontalFiltersValue . 'px) {';
            $css .= ".sidebar {$baseFilterSelector} {display:block!important;}";
            $css .= ".mst-nav__horizontal-bar {$baseFilterSelector} {display:none;}";

            if (!$this->horizontalFiltersConfig->getHasSidebarFilters()) {
                $css .= ".sidebar .block.filter.active .filter-options {display:block;}";
                $css .= ".sidebar .block.filter.active strong[role=heading] {display:block;}";
            }

            $css .= '} ';
        }

        $css .= '/* Display filters rules - end */';

        return $css;
    }

    private function getHorizontalFiltersCss(int $storeId, string $css): string
    {
        if ($hideHorizontalFiltersValue = $this->horizontalFiltersConfig->getHideHorizontalFiltersValue()) {
            $css .= '/* Hide horizontal filters if screen size is less than (px) - begin */';
            $css .= '@media all and (max-width: ' . $hideHorizontalFiltersValue . 'px) {';
            $css .= '.mst-nav__horizontal-bar .block-subtitle.filter-subtitle {display: none !important;} ';
            $css .= '.mst-nav__horizontal-bar .filter-options {display: none !important;} ';
            $css .= '} ';
            $css .= '/* Hide horizontal filters if screen size is less than (px) - end */';
        }

        if (count($this->horizontalFiltersConfig->getFilters()) == 0 && $this->stateBarConfigProvider->isHorizontalPosition() == false) {
            $css .= '.mst-nav__horizontal-bar {display:none}';
        }

        return $css;
    }

    private function getFilterClearBlockCss(int $storeId, string $css): string
    {
        if ($this->stateBarConfigProvider->isHorizontalPosition()) {
            $css .= '/* Show horizontal clear filter panel - begin */';
            $css .= '.navigation-horizontal {display: block !important;} ';
            $css .= '@media all and (mix-width: 767px) {';
            $css .= '.navigation-horizontal .block-actions.filter-actions {display: block !important;} ';
            $css .= '} ';
            $css .= '@media all and (max-width: 767px) {';
            $css .= '.navigation-horizontal .block-title.filter-title {display: none !important;} ';
            $css .= '} ';
            $css .= '.sidebar .block-actions.filter-actions {display: none;} ';
            $css .= '/* Show horizontal clear filter panel - end */';
        } else {
            $css .= '.navigation-horizontal .block-actions.filter-actions {display: none;} ';
        }

        if ($this->stateBarConfigProvider->isHidden()) {
            $css .= '.sidebar .block-actions.filter-actions {display: none;} ';
        }

        return $css;
    }

    private function getHighlightColorCss(int $storeId, string $css): string
    {
        $color = $this->highlightConfigProvider->getColor($storeId);

        $css .= $this->getStyle('.mst-nav__label .mst-nav__label-item._highlight a', [
            'color' => $color,
        ]);

        //        $css .= '.item .m-navigation-link-highlight { color:' . $color . '; } ';
        //        $css .= '.m-navigation-highlight-swatch .swatch-option.selected { outline: 2px solid ' . $color . '; } ';
        //        $css .= '.m-navigation-filter-item .swatch-option.image:not(.disabled):hover { outline: 2px solid'
        //            . $color . '; border: 1px solid #fff; } ';
        //        $css .= '.swatch-option.image.m-navigation-highlight-swatch { outline: 2px solid'
        //            . $color . '; 1px solid #fff; } ';
        //        $css .= '.m-navigation-swatch .swatch-option:not(.disabled):hover { outline: 2px solid'
        //            . $color . '; border: 1px solid #fff;  color: #333; } ';
        //        $css .= '.m-navigation-swatch .m-navigation-highlight-swatch .swatch-option { outline: 2px solid'
        //            . $color . '; border: 1px solid #fff;  color: #333; } ';
        //

        return $css;
    }

    private function getDisplayOptionsCss(int $storeId, string $css): string
    {
        if ($backgroundColor = $this->configProvider->getDisplayOptionsBackgroundColor()) {
            $css .= '.mst-nav__label .mst-nav__label-item input[type="checkbox"], '
                . '.mst-nav__label .mst-nav__label-item input[type="radio"] {background:'
                . $backgroundColor . '!important;}';
        }
        if ($borderColor = $this->configProvider->getDisplayOptionsBorderColor()) {
            $css .= '.mst-nav__label .mst-nav__label-item input[type="checkbox"], '
                . '.mst-nav__label .mst-nav__label-item input[type="radio"] {border-color:'
                . $borderColor . '!important;}';
        }
        if ($checkedColor = $this->configProvider->getDisplayOptionsCheckedColor()) {
            $css .= '.mst-nav__label .mst-nav__label-item input[type="checkbox"]:checked:before, '
                . '.mst-nav__label .mst-nav__label-item input[type="radio"]:checked:before {background:'
                . $checkedColor . '!important;}';
        }
        if ($sliderMainColor = $this->configProvider->getSliderMainColor()) {
            $css .= '.mst-nav__slider .mst-nav__slider-slider .ui-slider-range {background:' . $sliderMainColor . ';}';
        }
        if ($sliderSecondaryColor = $this->configProvider->getSliderSecondaryColor()) {
            $css .= '.mst-nav__slider .mst-nav__slider-slider {background:' . $sliderSecondaryColor . ';}';
        }

        return $css;
    }

    private function getShowOpenedFiltersCss(int $storeId, string $css): string
    {
        if ($isShowOpenedFilters = $this->configProvider->isOpenFilter()) {
            $css .= '.sidebar .filter-options .filter-options-content { display: block; } ';
        }

        return $css;
    }

    private function getStyle(string $selector, array $styles): string
    {
        $arr = [];

        foreach ($styles as $key => $value) {
            if ($value) {
                $arr[] = $key . ': ' . $value . ';';
            }
        }

        return $selector . '{' . implode($arr) . '}';
    }
}
