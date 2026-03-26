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

namespace Mirasvit\LayeredNavigation\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface as MagentoUrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Config as TaxConfig;
use Mirasvit\LayeredNavigation\Model\Config\Source\LinkTargetSource;
use Mirasvit\LayeredNavigation\Repository\AttributeConfigRepository;
use Mirasvit\LayeredNavigation\Model\Config\Source\FilterItemDisplayModeSource;
use Mirasvit\Core\Service\SerializeService;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Manager;

class ConfigProvider
{
    const AJAX_PRODUCT_LIST_WRAPPER_ID = 'm-navigation-product-list-wrapper';

    const NAV_REPLACER_TAG = '<div id="m-navigation-replacer"></div>'; //use for filter opener

    const MEDIA_FOLDER = 'mst_nav_group';

    const DEFAULT_BREAKPOINT = 768;

    const ATTRIBUTES_EXCEPTIONS = ['available_shipping_methods', 'shipping_class'];

    const USE_BRAND_SUFFIX = '2';

    private $scopeConfig;

    private $storeManager;

    private $filesystem;

    private $attributeConfigRepository;

    private $moduleManager;

    private $multiselectCache = [];

    public function __construct(
        ScopeConfigInterface      $scopeConfig,
        StoreManagerInterface     $storeManager,
        Filesystem                $filesystem,
        AttributeConfigRepository $attributeConfigRepository,
        Manager                   $moduleManager
    ) {
        $this->scopeConfig               = $scopeConfig;
        $this->storeManager              = $storeManager;
        $this->filesystem                = $filesystem;
        $this->attributeConfigRepository = $attributeConfigRepository;
        $this->moduleManager             = $moduleManager;
    }

    public function isSeoFiltersEnabled(): bool
    {
        if (!$this->moduleManager->isEnabled('Mirasvit_SeoFilter')) {
            return false;
        }

        $isEnabled = (bool)$this->scopeConfig->getValue('mst_seo_filter/general/is_enabled', ScopeInterface::SCOPE_STORE);

        if (!$isEnabled) {
            $isEnabled = ObjectManager::getInstance()->create('Mirasvit\SeoFilter\Service\RewriteService')->enabledAttrubteExists();
        }

        return $isEnabled;
    }

    public function getSeoFiltersPrefix(): string
    {
        return (string)$this->scopeConfig->getValue('mst_seo_filter/general/prefix', ScopeInterface::SCOPE_STORE);
    }

    public function isAjaxEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/general/is_ajax_enabled', ScopeInterface::SCOPE_STORE);
    }

    public function getApplyingMode(): string
    {
        return (string)$this->scopeConfig->getValue('mst_nav/general/filter_applying_mode', ScopeInterface::SCOPE_STORE);
    }

    public function isPreCalculationEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/general/filter_pre_calculation', ScopeInterface::SCOPE_STORE);
    }

    public function getIsConfirmOnMobile(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/general/confirm_on_mobile', ScopeInterface::SCOPE_STORE);
    }

    public function getBreakpointForModeSwitch(): int
    {
        $breakpoint = (int)$this->scopeConfig->getValue('mst_nav/general/mode_switch_breakpoint', ScopeInterface::SCOPE_STORE);

        return $breakpoint ?: self::DEFAULT_BREAKPOINT;
    }

    public function isShowNestedCategories(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/general/is_show_nested_categories', ScopeInterface::SCOPE_STORE);
    }

    public function isMultiselectEnabled(string $attributeCode = ''): bool
    {
        if (isset($this->multiselectCache[$attributeCode])) {
            return $this->multiselectCache[$attributeCode];
        }

        $isEnabled = (bool)$this->scopeConfig->getValue('mst_nav/general/is_multiselect_enabled', ScopeInterface::SCOPE_STORE);

        if (!$attributeCode) {
            $this->multiselectCache[$attributeCode] = $isEnabled;
            return $isEnabled;
        }

        $attribute = $this->attributeConfigRepository->getByAttributeCode($attributeCode);
        if ($attribute && $attribute->isMultiselectEnabled() !== null) {
            $isEnabled = $attribute->isMultiselectEnabled();
        }

        $this->multiselectCache[$attributeCode] = (bool)$isEnabled;

        return (bool)$isEnabled;
    }

    public function getFilterItemDisplayMode(string $attributeCode = ''): string
    {
        if ($attributeCode && !$this->isMultiselectEnabled($attributeCode)) {
            return FilterItemDisplayModeSource::OPTION_LINK;
        }

        return (string)$this->scopeConfig->getValue('mst_nav/general/filter_item_display_mode', ScopeInterface::SCOPE_STORE);
    }

    public function getDisplayOptionsBackgroundColor(): string
    {
        return (string)$this->scopeConfig->getValue('mst_nav/general/display_options_background_color', ScopeInterface::SCOPE_STORE);
    }

    public function getDisplayOptionsBorderColor(): string
    {
        return (string)$this->scopeConfig->getValue('mst_nav/general/display_options_border_color', ScopeInterface::SCOPE_STORE);
    }

    public function getDisplayOptionsCheckedColor(): string
    {
        return (string)$this->scopeConfig->getValue('mst_nav/general/display_options_checked_color', ScopeInterface::SCOPE_STORE);
    }

    public function getSliderMainColor(): string
    {
        return (string)$this->scopeConfig->getValue('mst_nav/styling/slider_main_color', ScopeInterface::SCOPE_STORE);
    }

    public function getSliderSecondaryColor(): string
    {
        return (string)$this->scopeConfig->getValue('mst_nav/styling/slider_secondary_color', ScopeInterface::SCOPE_STORE);
    }

    public function getAdditionalCss(): string
    {
        return (string)$this->scopeConfig->getValue('mst_nav/styling/additional_css', ScopeInterface::SCOPE_STORE);
    }

    public function isOpenFilter(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/general/is_open_filter', ScopeInterface::SCOPE_STORE);
    }

    public function getOpenedFiltersLimit(): int
    {
        $opened = (int)$this->scopeConfig->getValue('mst_nav/general/open_filter_limit', ScopeInterface::SCOPE_STORE);

        return $opened > 0 ? $opened : 9999;
    }

    public function isCorrectElasticFilterCount(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/general/is_correct_elastic_filter_count', ScopeInterface::SCOPE_STORE);
    }

    public function getSearchEngine(): string
    {
        return (string)$this->scopeConfig->getValue('catalog/search/engine', ScopeInterface::SCOPE_STORE);
    }

    public function isCategoryFilterVisibleInLayerNavigation(): bool
    {
        return $this->scopeConfig->getValue('catalog/layered_navigation/display_category') ? true : false;
    }

    public function getMediaPath(string $image): string
    {
        $path = $this->filesystem
                ->getDirectoryRead(DirectoryList::MEDIA)
                ->getAbsolutePath() . self::MEDIA_FOLDER;

        if (!file_exists($path) || !is_dir($path)) {
            $this->filesystem
                ->getDirectoryWrite(DirectoryList::MEDIA)
                ->create($path);
        }

        return $path . '/' . $image;
    }

    public function getMediaUrl(?string $image): ?string
    {
        if (!$image) {
            return null;
        }

        $url = $this->storeManager->getStore()
                ->getBaseUrl(MagentoUrlInterface::URL_TYPE_MEDIA) . self::MEDIA_FOLDER;

        $url .= '/' . $image;

        return $url;
    }

    public function isProductAttributeLinkingEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/product_attribute_linking/is_enabled', ScopeInterface::SCOPE_STORE);
    }

    public function getProductAttributeLinkTarget(): string
    {
        return $this->scopeConfig->getValue(
            'mst_nav/product_attribute_linking/target',
            ScopeInterface::SCOPE_STORE
        ) ?: LinkTargetSource::TARGET_SELF;
    }

    public function getProductAttributeLinkAttributes(): array
    {
        $attributes = $this->scopeConfig->getValue(
            'mst_nav/product_attribute_linking/attributes',
            ScopeInterface::SCOPE_STORE
        ) ?: LinkTargetSource::TARGET_SELF;

        return (array)SerializeService::decode($attributes);
    }

    public function getBrandsUrlSuffix(): string
    {
        return (string)$this->scopeConfig->getValue('brand/general/url_suffix', ScopeInterface::SCOPE_STORE);
    }

    public function getBrandsUrlSuffixCategory(): string
    {
        return (string)$this->scopeConfig->getValue('brand/general/url_suffix_category', ScopeInterface::SCOPE_STORE);
    }

    public function isIntegrateWithBrandsEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/product_attribute_linking/use_brand_page', ScopeInterface::SCOPE_STORE);
    }

    public function isHideUnusefulOptionEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/general/hide_unuseful_option', ScopeInterface::SCOPE_STORE);
    }

    public function isShowClearButton(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/general/show_clear_button', ScopeInterface::SCOPE_STORE);
    }

    public function getScrollToTopOption(): int
    {
        return (int)$this->scopeConfig->getValue('mst_nav/general/scroll_to_top', ScopeInterface::SCOPE_STORE);
    }

    public function isStickySidebarEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/general/sticky_sidebar', ScopeInterface::SCOPE_STORE);
    }

    public function isTaxIncluded(): bool
    {
        $priceDisplayType = (int)$this->scopeConfig->getValue(
            TaxConfig::CONFIG_XML_PATH_PRICE_DISPLAY_TYPE,
            ScopeInterface::SCOPE_STORE
        );

        if ($priceDisplayType === TaxConfig::DISPLAY_TYPE_EXCLUDING_TAX) {
            return false;
        }

        return $this->scopeConfig->isSetFlag('mst_nav/product_price/include_tax', ScopeInterface::SCOPE_STORE);
    }
}
