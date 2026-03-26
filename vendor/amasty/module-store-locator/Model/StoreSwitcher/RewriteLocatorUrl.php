<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Store Locator for Magento 2
 */

namespace Amasty\Storelocator\Model\StoreSwitcher;

use Amasty\Storelocator\Model\ConfigProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\PathInfo;
use Magento\Framework\HTTP\PhpEnvironment\RequestFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\StoreSwitcherInterface;

class RewriteLocatorUrl implements StoreSwitcherInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var PathInfo
     */
    protected $pathInfo;

    public function __construct(
        ConfigProvider $configProvider,
        ?RequestFactory $requestFactory, // @deprecated
        StoreManagerInterface $storeManager,
        ?UrlInterface $urlBuilder = null,
        ?PathInfo $pathInfo = null
    ) {
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder ?? ObjectManager::getInstance()->get(UrlInterface::class);
        $this->pathInfo = $pathInfo ?? ObjectManager::getInstance()->get(PathInfo::class);
    }

    public function switch(
        StoreInterface $fromStore,
        StoreInterface $targetStore,
        string $redirectUrl
    ): string {
        $pathInfo = $this->pathInfo->getPathInfo(
            $redirectUrl,
            trim($targetStore->getBaseUrl(), '/')
        );

        $urlPath = array_values(array_filter(explode('/', $pathInfo)));

        if (isset($urlPath[0]) && $urlPath[0] === $this->configProvider->getUrl($fromStore->getStoreId())) {
            $targetRedirectLocatorUrl = $this->urlBuilder->getUrl(
                $this->configProvider->getUrl($fromStore->getStoreId()),
                ['_scope' => $targetStore->getId()]
            );
            $targetLocatorUrl = $this->urlBuilder->getUrl(
                $this->configProvider->getUrl($targetStore->getId()),
                ['_scope' => $targetStore->getId()]
            );

            $redirectUrl = str_replace(
                trim($targetRedirectLocatorUrl, '/'),
                trim($targetLocatorUrl, '/'),
                trim($redirectUrl, '/') . '/'
            );
        }

        return $redirectUrl;
    }
}
