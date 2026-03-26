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

namespace Mirasvit\Brand\Model\Config;

use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;
use Magento\Store\Model\ScopeInterface;

class GeneralConfig extends BaseConfig
{
    const DEFAULT_ALL_BRAND_URL = 'brand';

    const XML_PATH_BRAND_URL_SUFFIX = 'brand/general/url_suffix';

    const BRAND_URL_SUFFIX_CATEGORY_ON  = 1;
    const BRAND_URL_SUFFIX_CATEGORY_OFF = 2;

    const BRAND_URL_SUFFIX_OFF          = 1;
    const BRAND_URL_SUFFIX_CATEGORY     = 2;
    const BRAND_URL_SUFFIX_CUSTOM       = 3;

    public function getBrandAttribute()
    {
        return $this->scopeConfig->getValue(
            'brand/general/BrandAttribute',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    public function getAllBrandUrl(?int $storeId = null): string
    {
        $allBrandUrl = (string)$this->scopeConfig->getValue(
            'brand/general/AllBrandUrl',
            ScopeInterface::SCOPE_STORE,
            $storeId ? : $this->storeId
        );

        return $allBrandUrl ? : self::DEFAULT_ALL_BRAND_URL;
    }

    public function getFormatBrandUrl(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            'brand/general/FormatBrandUrl',
            ScopeInterface::SCOPE_STORE,
            $storeId ? : $this->storeId
        );
    }

    public function getUrlSuffix()
    {
        if ($this->getUrlSuffixMode() == self::BRAND_URL_SUFFIX_OFF) {
            return '';
        }
        if ($this->getUrlSuffixMode() == self::BRAND_URL_SUFFIX_CATEGORY) {
            return $this->getCategoryUrlSuffix();
        } 
        if ($this->getUrlSuffixMode() == self::BRAND_URL_SUFFIX_CUSTOM) {
            return $this->getCustomBrandUrlSuffix();
        }

        // compatibility with old settings
        if ($this->isCategoryUrlSuffix()) {
            return $this->getCategoryUrlSuffix();
        }
        
        return $this->scopeConfig->getValue(self::XML_PATH_BRAND_URL_SUFFIX) ?? '';

    }

    public function getUrlSuffixMode()
    {
        return $this->scopeConfig->getValue(
            'brand/brand_page/seo/url_suffix_mode',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

  
    public function getCustomBrandUrlSuffix()
    {
        return $this->scopeConfig->getValue(
            'brand/brand_page/seo/custom_suffix',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }
    
    public function isCategoryUrlSuffix()
    {
        return ((int)$this->scopeConfig->getValue(self::XML_PATH_BRAND_URL_SUFFIX . '_category'))
            === self::BRAND_URL_SUFFIX_CATEGORY_ON;
    }

    public function getBrandLinkPosition()
    {
        return $this->scopeConfig->getValue(
            'brand/general/BrandLinkPosition',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    public function getBrandLinkPositionTemplate()
    {
        return $this->scopeConfig->getValue(
            'brand/general/BrandLinkPositionTemplate',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    public function getBrandsMenuMode(): int
    {
        return (int)$this->scopeConfig->getValue(
            'brand/general/menu_mode',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    public function getBrandLinkLabel()
    {
        return $this->scopeConfig->getValue(
            'brand/general/BrandLinkLabel',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    public function getBrandsMenuTitle(): ?string
    {
        return $this->scopeConfig->getValue(
            'brand/general/menu_title',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    public function isShowNotConfiguredBrands()
    {
        return $this->scopeConfig->getValue(
            'brand/general/isShowNotConfiguredBrands',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    public function isShowBrandsWithoutProducts()
    {
        return $this->scopeConfig->getValue(
            'brand/general/isShowBrandsWithoutProducts',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    public function isShowAllCategories()
    {
        return $this->scopeConfig->getValue(
            'brand/general/isShowAllCategories',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    public function isShowProductsInForm()
    {
        return $this->scopeConfig->getValue('brand/general/show_products_in_page_form');
    }

    /**
     * Retrieve category rewrite suffix for store.
     */
    private function getCategoryUrlSuffix(): string
    {
        return $this->scopeConfig->getValue(
            CategoryUrlPathGenerator::XML_PATH_CATEGORY_URL_SUFFIX,
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    public function showPageTitle(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            'brand/general/show_page_title',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    public function getPageTitle(): ?string
    {
        return (string)$this->scopeConfig->getValue(
            'brand/general/title',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }
}
