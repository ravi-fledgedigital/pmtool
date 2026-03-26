<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\ProductRecommendationsAdmin\Block\Adminhtml;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\ServicesId\Model\ServicesConfig;
use Magento\ServicesId\Model\ServicesConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\ProductRecommendationsAdmin\Model\ModuleVersionReader;

class Index extends Template
{
    /**
     * Config Paths
     * @var string
     */
    private const FRONTEND_URL_PATH = 'product_recommendations/frontend_url';
    const CONFIG_PATH_ALTERNATE_ENVIRONMENT_ENABLED = 'services_connector/product_recommendations/alternate_environment_enabled';
    const CONFIG_PATH_ALTERNATE_ENVIRONMENT_ID = 'services_connector/product_recommendations/alternate_environment_id';

    /**
     * @var ServicesConfigInterface
     */
    private $servicesConfig;

    /**
     * @var ModuleVersionReader
     */
    private $moduleVersionReader;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param ServicesConfigInterface $servicesConfig
     * @param ModuleVersionReader $moduleVersionReader
     */
    public function __construct(
        Context $context,
        ServicesConfigInterface $servicesConfig,
        ModuleVersionReader $moduleVersionReader
    ) {
        $this->servicesConfig = $servicesConfig;
        $this->moduleVersionReader = $moduleVersionReader;
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
     * Get website code
     *
     * @return string
     * @throws LocalizedException
     */
    public function getWebsiteCode(): string
    {
        $storeId = $this->getRequest()->getParam('store');
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
        return $this->_storeManager->getWebsite($websiteId)->getCode();
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
     * Get Environment Name from Services Id configuration
     *
     * @return string|null
     */
    public function getEnvironmentName(): ?string
    {
        return $this->servicesConfig->getEnvironmentName();
    }

    /**
     * Get Environment Type from Services Id configuration
     *
     * @return string|null
     */
    public function getEnvironmentType(): ?string
    {
        return $this->servicesConfig->getEnvironmentType();
    }

    /**
     * Get alternate Environment Id to fetch recommendations from configuration
     *
     * @return string|null
     */
    public function getAlternateEnvironmentId(): ?string
    {
        return $this->_scopeConfig->getValue(self::CONFIG_PATH_ALTERNATE_ENVIRONMENT_ID);
    }

    /**
     * Check if alternate recommendations environment is being used for recommendations
     *
     * @return bool
     */
    public function isAlternateEnvironmentEnabled(): bool
    {
        return (bool) $this->_scopeConfig->getValue(self::CONFIG_PATH_ALTERNATE_ENVIRONMENT_ENABLED);
    }

    /**
     * Get API Key from Services Connector configuration.
     *
     * Return hardcoded value if IMS is enabled.
     *
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->isImsEnabled()
            ? 'prex-api-key'
            : $this->_scopeConfig->getValue(ServicesConfig::CONFIG_PATH_SERVICES_CONNECTOR_PRODUCTION_API_KEY);
    }

    /**
     * Check if API keys are set
     *
     * @return bool
     */
    public function isApiKeySet(): bool
    {
        return $this->servicesConfig->isApiKeySet();
    }

   /**
     * Retrieve store current currency code
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCurrentCurrencyCode()
    {
        $storeId = $this->getRequest()->getParam('store');
        return $this->_storeManager->getStore($storeId)->getCurrentCurrencyCode();
    }

    /**
     * Get module version
     *
     * @return string|null
     */
    public function getModuleVersion(): ?string
    {
        return $this->moduleVersionReader->getVersion();
    }

    /**
     * Check whether environment is IMS
     * @return bool
     */
    public function isImsEnabled(): bool
    {
        return (bool) $this->_scopeConfig->getValue(ServicesConfig::CONFIG_PATH_IMS_ENABLED);
    }
}
