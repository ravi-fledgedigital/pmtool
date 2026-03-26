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
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\CatalogLabel\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface as ScopeInterface;

class ConfigProvider
{
    protected $storeManager;

    protected $filesystem;

    protected $context;

    protected $scopeConfig;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Model\Context $context,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->storeManager = $storeManager;
        $this->filesystem   = $filesystem;
        $this->context      = $context;
        $this->scopeConfig  = $scopeConfig;
    }

    public function getBaseMediaPath(): string
    {
        return $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)
            ->getAbsolutePath().'cataloglabel';
    }

    public function getBaseMediaUrl(): string
    {
        return $this->storeManager->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'cataloglabel';
    }

    public function getBaseTmpMediaPath(): string
    {
        return $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)
            ->getAbsolutePath().'cataloglabel';
    }

    public function isFlushCacheEnabled(?int $store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            'cataloglabel/general/is_flush_cache_enabled',
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
    }

    public function getDiscountOutputPrecision(): int
    {
        return (int)$this->scopeConfig->getValue(
            'cataloglabel/general/discount_precision',
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
    }

    public function getDateFormat(): string
    {
        return $this->scopeConfig->getValue(
            'cataloglabel/general/date_format',
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        ) ?? '';
    }

    public function getIgnorePages(): array
    {
        $result = [];
        $pages = trim((string)$this->scopeConfig->getValue('cataloglabel/general/ignore_pages'));

        if ($pages) {
            $pages = explode("\n", $pages);
            $result = array_map('trim', $pages);
        }

        return $result;
    }

    public function isApplyForChild(): bool
    {
        return (bool)$this->scopeConfig->getValue(
                'cataloglabel/general/apply_for_child',
                ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore()->getId()
            );
    }

    public function isApplyForParent(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            'cataloglabel/general/apply_for_parent',
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
    }

    public function isIgnoredPage(string $actionName, string $url): bool
    {
        foreach ($this->getIgnorePages() as $page) {
            if ($this->checkPattern($actionName, $page)
                || $this->checkPattern($url, $page)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function checkPattern(string $string, string $pattern): bool
    {
        $string  = strtolower($string);
        $pattern = strtolower($pattern);

        $parts = explode('*', $pattern);
        $index = 0;

        $shouldBeFirst = true;

        foreach ($parts as $part) {
            if ($part == '') {
                $shouldBeFirst = false;
                continue;
            }

            $index = strpos($string, $part, $index);

            if ($index === false) {
                return false;
            }

            if ($shouldBeFirst && $index > 0) {
                return false;
            }

            $shouldBeFirst = false;
            $index         += strlen($part);
        }

        if (count($parts) == 1) {
            return $string == $pattern;
        }

        $last = end($parts);
        if ($last == '') {
            return true;
        }

        if (strrpos($string, $last) === false) {
            return false;
        }

        if (strlen($string) - strlen($last) - strrpos($string, $last) > 0) {
            return false;
        }

        return true;
    }
}
