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

namespace Mirasvit\Brand\Service;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Result\Page;
use Mirasvit\Brand\Api\Data\BrandPageInterface;
use Mirasvit\Brand\Model\Config\Config;
use Mirasvit\Brand\Registry;

class BrandPageMetaService
{
    private $brandUrlService;

    private $storeManager;

    private $config;

    private $registry;

    private $context;

    public function __construct(
        BrandUrlService $brandUrlService,
        Config $config,
        Registry $registry,
        Context $context
    ) {
        $this->brandUrlService = $brandUrlService;
        $this->registry        = $registry;
        $this->storeManager    = $context->getStoreManager();
        $this->config          = $config;
        $this->context         = $context;
    }

    public function apply(Page $page, bool $isIndexPage = false): Page
    {
        $pageConfig = $page->getConfig();

        $pageConfig->getTitle()->set((string)__($this->getMetaTitle($isIndexPage)));

        $pageConfig->setMetadata('description', (string)__($this->getMetaDescription($isIndexPage)));
        $pageConfig->setMetadata('keywords', (string)__($this->getKeyword($isIndexPage)));
        $pageConfig->setMetadata('robots', $this->getRobots($isIndexPage));

        if ($pageConfig->getAssetCollection()->getGroupByContentType('canonical')
            && !empty($pageConfig->getAssetCollection()->getGroupByContentType('canonical')->getAll())) {
            foreach ($pageConfig->getAssetCollection()->getGroupByContentType('canonical')->getAll() as $key => $value) {
                $pageConfig->getAssetCollection()->remove($key);
            }
        }

        $pageConfig->addRemotePageAsset(
            $this->getCanonical($isIndexPage),
            'canonical',
            ['attributes' => ['rel' => 'canonical']]
        );

        $layout = $this->context->getLayout();

        if ($pageMainTitle = $layout->getBlock('page.main.title')) {
            $pageMainTitle->setPageTitle($this->getTitle($isIndexPage));
        }

        return $page;
    }

    private function getTitle(bool $isIndexPage): string
    {
        if ($isIndexPage) {
            return ' ';
        }

        return $this->getBrandPage()->getBrandTitle()
            ? $this->getBrandPage()->getBrandTitle()
            : $this->getBrandPage()->getBrandName();
    }

    private function getMetaTitle(bool $isIndexPage): string
    {
        if ($isIndexPage) {
            return $this->config->getAllBrandPageConfig()->getMetaTitle()
                ? $this->config->getAllBrandPageConfig()->getMetaTitle()
                : (string)__('Brands');
        }

        $brandPage = $this->getBrandPage();

        if ($brandPage->getMetaTitle()) {
            $metaTitle = $brandPage->getMetaTitle();
        } elseif ($brandPage->getBrandTitle()) {
            $metaTitle = $brandPage->getBrandTitle();
        } elseif ($brandPage->getBrandName()) {
            $metaTitle = $brandPage->getBrandName();
        } else {
            $metaTitle = $this->registry->getBrand()->getLabel();
        }

        return $metaTitle;
    }

    private function getKeyword(bool $isIndexPage): string
    {
        if ($isIndexPage) {
            return $this->config->getAllBrandPageConfig()->getMetaKeyword();
        }

        $metaKeywords = $this->getBrandPage()->getKeyword()
            ? $this->getBrandPage()->getKeyword()
            : $this->getBrandPage()->getBrandName();

        return $metaKeywords ?: $this->registry->getBrand()->getLabel();
    }

    private function getMetaDescription(bool $isIndexPage): string
    {
        if ($isIndexPage) {
            return $this->config->getAllBrandPageConfig()->getMetaDescription();
        }

        $metaDescription = $this->getBrandPage()->getMetaDescription()
            ? $this->getBrandPage()->getMetaDescription()
            : $this->getBrandPage()->getBrandName();

        return $metaDescription ?: $this->registry->getBrand()->getLabel();
    }

    private function getCanonical(bool $isIndexPage): string
    {
        if ($isIndexPage) {
            return $this->brandUrlService->getBaseBrandUrl();
        }
        $canonical = $this->getBrandPage()->getCanonical();

        if ($canonical) {
            if (strpos('http:', $canonical) !== false
                || strpos('https:', $canonical) !== false) {
                return $canonical;
            } else {
                return $this->brandUrlService->getBaseBrandUrl() . '/' . ltrim($canonical, '/');
            }
        }

        $suffix       = $this->config->getGeneralConfig()->getUrlSuffix() ? : '';
        $brandBaseUrl = $this->brandUrlService->getBaseBrandUrl();

        if ($suffix && strrpos($brandBaseUrl, $suffix) !== false) {
            $brandBaseUrl = substr($brandBaseUrl, 0, strrpos($brandBaseUrl, $suffix));
        }

        $urlKey = $this->getBrandPage()->getUrlKey() ?: $this->registry->getBrand()->getUrlKey();

        return $brandBaseUrl . '/' . $urlKey . $suffix;
    }

    private function getRobots(bool $isIndexPage): string
    {
        $indexFollow = 'INDEX,FOLLOW';

        if ($isIndexPage) {
            return $indexFollow;
        }

        return $this->getBrandPage()->getRobots()
            ? $this->getBrandPage()->getRobots()
            : $indexFollow;
    }

    private function getBrandPage(): ?BrandPageInterface
    {
        return $this->registry->getBrandPage();
    }
}
