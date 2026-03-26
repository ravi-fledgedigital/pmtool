<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogSyncAdmin\Block\Adminhtml;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\GraphQlServer\Model\UrlProvider;
use Magento\ServicesId\Model\ServicesConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @api
 */
class Index extends Template
{
    /**
     * Config Paths
     * @var string
     */
    private const FRONTEND_URL_PATH = 'catalog_sync_admin/frontend_url';
    private const FRONTEND_CSS_URL_PATH = 'catalog_sync_admin/frontend_css_url';

    /**
     * @var ServicesConfigInterface
     */
    private $servicesConfig;

    /**
     * @var UrlProvider
     */
    private $graphQlUrlProvider;

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param Context $context
     * @param ServicesConfigInterface $servicesConfig
     * @param UrlProvider $graphQlUrl
     * @param Manager $moduleManager
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context $context,
        ServicesConfigInterface $servicesConfig,
        UrlProvider $graphQlUrl,
        Manager $moduleManager,
        SerializerInterface $serializer
    ) {
        $this->servicesConfig = $servicesConfig;
        $this->graphQlUrlProvider = $graphQlUrl;
        $this->moduleManager = $moduleManager;
        $this->serializer = $serializer;
        parent::__construct($context);
    }

    /**
     * Returns config for frontend url
     *
     * @return string
     */
    public function getFrontendUrl(): string
    {
        return (string) $this->_scopeConfig->getValue(
            self::FRONTEND_URL_PATH,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Returns config for frontend css url
     *
     * @return string
     */
    public function getFrontendCssUrl(): string
    {
        return (string) $this->_scopeConfig->getValue(
            self::FRONTEND_CSS_URL_PATH,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Returns GraphQl BFF url
     *
     * @return string
     */
    public function getGraphQlUrl(): string
    {
        return $this->graphQlUrlProvider->getUrl();
    }

    /**
     * Get website code from store switcher
     *
     * @return string
     * @throws LocalizedException
     */
    public function getWebsiteCode(): string
    {
        $websiteId = $this->_storeManager->getStore($this->getStoreViewCode())->getWebsiteId();
        return $this->_storeManager->getWebsite($websiteId)->getCode();
    }

    /**
     * Get store code from store switcher
     *
     * @return string
     * @throws LocalizedException
     */
    public function getStoreCode(): string
    {
        $groupId = $this->_storeManager->getStore($this->getStoreViewCode())->getStoreGroupId();
        return $this->_storeManager->getGroup($groupId)->getCode();
    }

    /**
     * Get store view code from store switcher
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreViewCode(): string
    {
        $storeId = $this->getRequest()->getParam('store');
        return $this->_storeManager->getStore($storeId)->getCode();
    }

    /**
     * Get Environment Id from Services Id configuration
     *
     * @return string|null
     */
    public function getEnvironmentId(): ?string
    {
        return $this->servicesConfig->getEnvironmentId();
    }

    /**
     * Get Enabled Modules from module manager
     *
     * @return string
     */
    public function getEnabledModules(): string
    {
        $enabledModules = [
            'liveSearch' => $this->moduleManager->isEnabled("Magento_LiveSearch"),
            'productRecommendations' => $this->moduleManager->isEnabled("Magento_ProductRecommendationsAdmin"),
            'catalogService' => $this->moduleManager->isEnabled("Magento_CatalogServiceInstaller")
        ];

        return $this->serializer->serialize($enabledModules);
    }
}
