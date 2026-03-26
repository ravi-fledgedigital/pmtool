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

use Magento\Framework\DataObject;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Brand\Api\Data\BrandInterface;
use Mirasvit\Brand\Api\Data\BrandPageInterface;
use Mirasvit\Brand\Model\Config\Config;
use Mirasvit\Brand\Service\BrandAttributeService;

class BrandUrlService
{
    const LONG_URL  = 0;
    const SHORT_URL = 1;

    private $brandAttributeService;

    private $config;

    private $storeManager;

    private $filterManager;

    public function __construct(
        StoreManagerInterface $storeManager,
        BrandAttributeService $brandAttributeService,
        Config                $config,
        FilterManager         $filterManager
    ) {
        $this->brandAttributeService = $brandAttributeService;
        $this->config                = $config;
        $this->storeManager          = $storeManager;
        $this->filterManager         = $filterManager;
    }

    public function getBaseBrandUrl(?int $storeId = 0): string
    {
        if ($storeId) {
            return $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_LINK, true)
                . $this->getBaseRoute(true, $storeId);
        }

        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK, true)
            . $this->getBaseRoute(true);
    }

    public function getBrandUrl(BrandInterface $brand, ?int $storeId = null): string
    {
        if ($storeId === null) {
            $storeId = (int)$this->storeManager->getStore()->getId();
        }

        $urlKey = $brand->getUrlKey();

        $formatBrandUrl = $this->config->getGeneralConfig()->getFormatBrandUrl();

        if ($formatBrandUrl === self::SHORT_URL) {
            $brandUrl = $urlKey;
        } else {
            $brandUrl = $this->getBaseRoute(false, $storeId) . '/' . $urlKey;
        }

        $brandUrl = $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_LINK, true)
            . $brandUrl;

        return $brandUrl . $this->config->getGeneralConfig()->getUrlSuffix();
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function match(string $pathInfo, ?int $storeId = null): ?DataObject
    {
        $suffix = $this->config->getGeneralConfig()->getUrlSuffix() ? : '';

        if ($suffix) {
            if (substr($pathInfo, (int)strrpos($pathInfo, $suffix)) !== $suffix) {
                return null;
            }

            $pathInfo = substr($pathInfo, 0, strrpos($pathInfo, $suffix));
        }

        $identifier = trim($pathInfo, '/');
        $parts      = explode('/', $identifier);

        $requestBrandUrlKeys  = $this->getAvailableBrandUrlKeys($storeId);
        $responseBrandUrlKeys = $this->getAvailableBrandUrlKeys();
        $urlKey               = $parts[0];

        if (
            $urlKey !== $this->getBaseRoute(false, $storeId)
            && !in_array($urlKey, $requestBrandUrlKeys, true)
            || !$this->isUrlValid($requestBrandUrlKeys, $identifier, $storeId)
        ) {
            return null;
        }

        $urlType           = $this->config->getGeneralConfig()->getFormatBrandUrl($storeId);
        $requestBaseRoute  = $this->getBaseRoute(false, $storeId);
        $responseBaseRoute = $this->getBaseRoute();

        if (
            $urlType === self::SHORT_URL
            && $urlKey != $requestBaseRoute
            && in_array($urlKey, $requestBrandUrlKeys, true)
        ) {
            $optionId = array_search($urlKey, $requestBrandUrlKeys, true);

            if (!isset($responseBrandUrlKeys[$optionId])) {
                return null;
            }

            return new DataObject([
                'module_name'     => 'brand',
                'controller_name' => 'brand',
                'action_name'     => 'view',
                'route_name'      => $responseBrandUrlKeys[$optionId],
                'params'          => [BrandPageInterface::ATTRIBUTE_OPTION_ID => $optionId],
            ]);
        } elseif (isset($parts[1]) && in_array($parts[1], $requestBrandUrlKeys, true)) {
            $optionId = array_search($parts[1], $requestBrandUrlKeys, true);

            if (!isset($responseBrandUrlKeys[$optionId])) {
                return null;
            }

            return new DataObject([
                'module_name'     => 'brand',
                'controller_name' => 'brand',
                'action_name'     => 'view',
                'route_name'      => $responseBrandUrlKeys[$optionId],
                'params'          => [BrandPageInterface::ATTRIBUTE_OPTION_ID => $optionId],
            ]);
        } elseif ($urlKey === $requestBaseRoute && !isset($parts[1])) {
            return new DataObject([
                'module_name'     => 'brand',
                'controller_name' => 'brand',
                'action_name'     => 'index',
                'route_name'      => $responseBaseRoute,
                'params'          => [],
            ]);
        }

        return null;
    }

    private function getBaseRoute(bool $withSuffix = false, ?int $storeId = null): string
    {
        if ($storeId === null) {
            $storeId = (int)$this->storeManager->getStore()->getId();
        }

        $baseRoute = $this->config->getGeneralConfig()->getAllBrandUrl($storeId);

        if ($withSuffix) {
            $baseRoute .= $this->config->getGeneralConfig()->getUrlSuffix();
        }

        return $baseRoute;
    }

    /**
     * @return string[]
     */
    private function getAvailableBrandUrlKeys(?int $storeId = null): array
    {
        $urlKeys = [$this->getBaseRoute(false, $storeId)];
        $brandPages = $this->brandAttributeService->getBrandPagesByOptions($storeId);
        
        foreach ($brandPages as $brandPage) {
            $urlKeys[$brandPage[BrandPageInterface::ATTRIBUTE_OPTION_ID]] = 
                $brandPage->getStoreValue(BrandPageInterface::URL_KEY, $storeId) 
                ?: $brandPage[BrandPageInterface::URL_KEY];
        }

        if ($this->config->getGeneralConfig()->isShowNotConfiguredBrands()) {
            $visibleOptions = $this->brandAttributeService->getVisibleBrandOptions($storeId);

            foreach ($visibleOptions as $option) {
                $optionId = $option[BrandInterface::ID];

                if (isset($urlKeys[$optionId])) {
                    continue;
                }

                $label = $option[BrandInterface::LABEL] ?? '';
                if ($label) {
                    $urlKeys[$optionId] = $this->filterManager->translitUrl($label);
                }
            }
        }

        return $urlKeys;
    }

    private function isUrlValid(array $brandUrlKeys, string $identifier, ?int $storeId = null): bool
    {
        if ($this->config->getGeneralConfig()->getFormatBrandUrl($storeId) === self::LONG_URL) {
            
            $prefix = $brandUrlKeys[0] . '/';
            
            $pos = strpos($identifier, $prefix);
            
            if ($pos !== false) {

                $identifier = substr_replace($identifier, '', $pos, strlen($prefix));
                
                if ($identifier === $brandUrlKeys[0]) {
                    return false;
                }
            }
        }

        if (in_array($identifier, $brandUrlKeys)) {
            return true;
        }

        return false;
    }
}
