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
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;

class ExtraFilterConfigProvider
{
    const NEW_FILTER                   = 'mst_new_products';
    const ON_SALE_FILTER               = 'mst_on_sale';
    const STOCK_FILTER                 = 'mst_stock_status';
    const IN_STOCK_FILTER              = 2;
    const OUT_OF_STOCK_FILTER          = 1;
    const RATING_FILTER                = 'mst_rating_summary';
    const SEARCH_FILTER                = 'search';
    const NEW_FILTER_FRONT_PARAM       = 'mst_new_products';
    const ON_SALE_FILTER_FRONT_PARAM   = 'mst_on_sale';
    const STOCK_FILTER_FRONT_PARAM     = 'mst_stock';
    const RATING_FILTER_FRONT_PARAM    = 'mst_rating';
    const SEARCH_FILTER_FRONT_PARAM    = 'search';
    const NEW_FILTER_DEFAULT_LABEL     = 'New';
    const ON_SALE_FILTER_DEFAULT_LABEL = 'Sale';
    const STOCK_FILTER_DEFAULT_LABEL   = 'Stock';
    const RATING_FILTER_DEFAULT_LABEL  = 'Rating';
    const SEARCH_FILTER_DEFAULT_LABEL  = 'Search within results';
    const CATEGORY_FILTER_MODE_FILTER  = 'mode_category_filter';
    const CATEGORY_FILTER_MODE_LINK    = 'mode_category_link';

    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function isFilterEnabled(string $filter): bool
    {
        $method = 'is' . $this->transformToMethod($filter) . 'FilterEnabled';
        if (!method_exists($this, $method)) {
            throw new LocalizedException(__('Filter type "%1" does not exist', $filter));
        }

        return $this->{$method}();
    }

    public function getFilterPosition(string $filter): int
    {
        $method = 'get' . $this->transformToMethod($filter) . 'FilterPosition';

        if (!method_exists($this, $method)) {
            throw new LocalizedException(__('Filter type "%1" does not exist', $filter));
        }

        return $this->{$method}();
    }

    public function isNewFilterEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/extra_filter/new/is_enabled', ScopeInterface::SCOPE_STORE);
    }

    public function getNewFilterLabel(): string
    {
        return (string)$this->scopeConfig->getValue('mst_nav/extra_filter/new/label', ScopeInterface::SCOPE_STORE);
    }

    public function getNewFilterPosition(): int
    {
        return (int)$this->scopeConfig->getValue('mst_nav/extra_filter/new/position', ScopeInterface::SCOPE_STORE);
    }

    public function isOnSaleFilterEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/extra_filter/sale/is_enabled', ScopeInterface::SCOPE_STORE);
    }

    public function getOnSaleFilterLabel(): string
    {
        return (string)$this->scopeConfig->getValue('mst_nav/extra_filter/sale/label', ScopeInterface::SCOPE_STORE);
    }

    public function getOnSaleFilterPosition(): int
    {
        return (int)$this->scopeConfig->getValue('mst_nav/extra_filter/sale/position', ScopeInterface::SCOPE_STORE);
    }

    public function isOnSaleFilterHideNoOption(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/extra_filter/sale/hide_no_option', ScopeInterface::SCOPE_STORE);
    }

    public function isStockFilterEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/extra_filter/stock/is_enabled', ScopeInterface::SCOPE_STORE);
    }

    public function getStockFilterLabel(): string
    {
        return (string)$this->scopeConfig->getValue('mst_nav/extra_filter/stock/label', ScopeInterface::SCOPE_STORE);
    }

    public function getInStockFilterLabel(): string
    {
        return (string)$this->scopeConfig->getValue('mst_nav/extra_filter/stock/label_in', ScopeInterface::SCOPE_STORE);
    }

    public function getOutOfStockFilterLabel(): string
    {
        return (string)$this->scopeConfig->getValue('mst_nav/extra_filter/stock/label_out', ScopeInterface::SCOPE_STORE);
    }

    public function getStockFilterPosition(): int
    {
        return (int)$this->scopeConfig->getValue('mst_nav/extra_filter/stock/position', ScopeInterface::SCOPE_STORE);
    }

    public function isRatingFilterEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/extra_filter/rating/is_enabled', ScopeInterface::SCOPE_STORE);
    }

    public function getRatingFilterLabel(): string
    {
        return $this->scopeConfig->getValue('mst_nav/extra_filter/rating/label', ScopeInterface::SCOPE_STORE);
    }

    public function getRatingFilterPosition(): int
    {
        return (int)$this->scopeConfig->getValue('mst_nav/extra_filter/rating/position', ScopeInterface::SCOPE_STORE);
    }

    public function isSearchFilterEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/extra_filter/search/is_enabled', ScopeInterface::SCOPE_STORE);
    }

    public function isSearchFilterFulltext(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/extra_filter/search/is_fulltext', ScopeInterface::SCOPE_STORE);
    }

    public function isSearchFilterOptions(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/extra_filter/search/is_filter_options', ScopeInterface::SCOPE_STORE);
    }

    public function getSearchFilterLabel(): string
    {
        return (string)$this->scopeConfig->getValue('mst_nav/extra_filter/search/label', ScopeInterface::SCOPE_STORE);
    }

    public function getSearchFilterPosition(): int
    {
        return 0;
    }

    public function isShowNestedCategories(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/general/is_show_nested_categories', ScopeInterface::SCOPE_STORE);
    }

    public function isCategoriesCollapsible(): bool
    {
        return $this->isShowNestedCategories()
            && (bool)$this->scopeConfig->getValue('mst_nav/extra_filter/category/is_nested_collapsible', ScopeInterface::SCOPE_STORE);
    }

    public function getCategoryFilterSortOptionsBy(): string
    {
        $value = $this->scopeConfig->getValue('mst_nav/extra_filter/category/sort_by', ScopeInterface::SCOPE_STORE);

        return $value ? (string)$value : AttributeConfigInterface::OPTION_SORT_BY_POSITION;
    }

    public function isUseAlphabeticalIndexForCategoryFilter(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_nav/extra_filter/category/alphabetical_index', ScopeInterface::SCOPE_STORE);
    }

    public function transformToMethod(string $str): string
    {
        $str = str_replace('mst_', '', $str);
        return str_replace('_', '', ucwords($str, '_'));
    }

    public function isCategoryFilterModeLink(): bool
    {
        $categoryFilterMode = (string)$this->scopeConfig->getValue('mst_nav/extra_filter/category/mode', ScopeInterface::SCOPE_STORE);

        return $categoryFilterMode === self::CATEGORY_FILTER_MODE_LINK;
    }
}
