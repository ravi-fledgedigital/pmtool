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

namespace Mirasvit\LandingPage\Model\Config;

use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class ConfigProvider
{
    const URL_SUFFIX_OFF      = 1;
    const URL_SUFFIX_CATEGORY = 2;
    const URL_SUFFIX_CUSTOM   = 3;

    const INDEX_TABLE = 'mst_landing_page_product_index';

    private $scopeConfig;

    private $storeId;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeId     = $storeManager->getStore()->getStoreId();
    }

    public function isRelatedPagesEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'mst_landing_page/related_pages/enabled',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    /**
     * @return string Position string (e.g. "content.aside", "content/-") or empty if disabled.
     */
    public function getProductPosition(): string
    {
        return trim((string)$this->scopeConfig->getValue(
            'mst_landing_page/related_pages/product_position',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        ));
    }

    /**
     * @return string Position string or empty if disabled.
     */
    public function getCategoryPosition(): string
    {
        return trim((string)$this->scopeConfig->getValue(
            'mst_landing_page/related_pages/category_position',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        ));
    }

    /**
     * @return string Position string or empty if disabled.
     */
    public function getLandingPosition(): string
    {
        return trim((string)$this->scopeConfig->getValue(
            'mst_landing_page/related_pages/landing_position',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        ));
    }

    public function getMaxLinks(): int
    {
        return (int)($this->scopeConfig->getValue(
            'mst_landing_page/related_pages/max_links',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        ) ?: 10);
    }

    public function getBlockTitle(): string
    {
        return (string)($this->scopeConfig->getValue(
            'mst_landing_page/related_pages/block_title',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        ) ?: '');
    }

    public function getUrlSuffix(): string
    {
        if ($this->getUrlSuffixMode() == self::URL_SUFFIX_CATEGORY) {
            return $this->getCategoryUrlSuffix();
        }
        if ($this->getUrlSuffixMode() == self::URL_SUFFIX_CUSTOM) {
            return $this->getCustomUrlSuffix();
        }

        return '';
    }

    public function getUrlSuffixMode(): int
    {
        return (int)$this->scopeConfig->getValue(
            'mst_landing_page/seo/url_suffix_mode',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    public function getCustomUrlSuffix(): string
    {
        return (string)$this->scopeConfig->getValue(
            'mst_landing_page/seo/custom_suffix',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    /**
     * Retrieve category rewrite suffix for store.
     */
    private function getCategoryUrlSuffix(): string
    {
        return (string)$this->scopeConfig->getValue(
            CategoryUrlPathGenerator::XML_PATH_CATEGORY_URL_SUFFIX,
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }
}
