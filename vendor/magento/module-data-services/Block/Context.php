<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataServices\Block;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Cookie\Helper\Cookie;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\DataServices\Model\VersionFinderInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\ServicesId\Model\ServicesConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Context base block class
 *
 * @api
 */
class Context extends Template
{
    /**
     * Config paths
     */
    private const EXTENSION_VERSION_CONFIG_PATH = 'dataservices/version';

    /**
     * Cache keys
     */
    private const STOREFRONT_INSTANCE_CONTEXT_CACHE_KEY = 'dataservices_storefront_instance_context_';
    private const CATALOG_EXPORTER_VERSION_CACHE_KEY = 'catalog_exporter_extension_version';

    /**
     * Cache tags
     */
    private const STOREFRONT_INSTANCE_CONTEXT_CACHE_TAGS = ['full_page', 'config'];
    private const CATALOG_EXPORTER_VERSION_CACHE_TAGS = ['full_page', 'config'];

    /**
     * Extension constants
     */
    private const CATALOG_EXPORTER_MODULE_NAME = 'Magento/SaaSCommon';
    private const CATALOG_EXPORTER_PACKAGE_NAME = 'magento/module-saas-common';

    /**
     * @var Json
     */
    private Json $jsonSerializer;

    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;
    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $config;

    /**
     * @var ServicesConfigInterface
     */
    private ServicesConfigInterface $servicesConfig;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var CacheInterface
     */
    private CacheInterface $cacheInterface;

    /**
     * @var VersionFinderInterface
     */
    private VersionFinderInterface $versionFinder;

    /**
     * @var Cookie
     */
    private Cookie $cookieHelper;

    /**
    * @var CustomerSession
    */
    private CustomerSession $customerSession;

    /**
     * @var null|string
     */
    private $accountEmail;

    /**
     * @param Template\Context $context
     * @param Json $jsonSerializer
     * @param CheckoutSession $checkoutSession
     * @param ScopeConfigInterface $config
     * @param ServicesConfigInterface $servicesConfig
     * @param StoreManagerInterface $storeManager
     * @param CacheInterface $cacheInterface
     * @param VersionFinderInterface $versionFinder
     * @param Cookie $cookieHelper
     * @param CustomerSession $customerSession
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Json $jsonSerializer,
        CheckoutSession $checkoutSession,
        ScopeConfigInterface $config,
        ServicesConfigInterface $servicesConfig,
        StoreManagerInterface $storeManager,
        CacheInterface $cacheInterface,
        VersionFinderInterface $versionFinder,
        Cookie $cookieHelper,
        CustomerSession $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->jsonSerializer = $jsonSerializer;
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
        $this->servicesConfig = $servicesConfig;
        $this->storeManager = $storeManager;
        $this->cacheInterface = $cacheInterface;
        $this->versionFinder = $versionFinder;
        $this->cookieHelper = $cookieHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * Get context Json for events
     *
     * @return string
     */
    public function getEventContext(): string
    {
        $context = [];
        $viewModel = $this->getViewModel();
        if ($viewModel) {
            $context = $viewModel->getModelContext();
        }
        return $this->jsonSerializer->serialize($context);
    }

    /**
     * Return cart id for event tracking
     *
     * @return int
     */
    public function getCartId(): int
    {
        return (int) $this->checkoutSession->getQuoteId();
    }

    /**
     * Return coupon code for event tracking
     *
     * @return string|null
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getCouponCode(): ?string
    {
        return (string) $this->checkoutSession->getQuote()->getCouponCode();
    }

    /**
     * Return storefront-instance context for data services events
     *
     * @return string
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getStorefrontInstanceContext(): string
    {
        $store = $this->storeManager->getStore();
        $storeId = $store->getId();
        $currentCurrencyCode = $store->getCurrentCurrencyCode();
        $cacheId = self::STOREFRONT_INSTANCE_CONTEXT_CACHE_KEY . $storeId . '_' . $currentCurrencyCode;
        $context = $this->cacheInterface->load($cacheId);

        if (!$context) {
            $website = $this->storeManager->getWebsite();
            $group = $this->storeManager->getGroup();
            $contextData = [
                'storeUrl' => $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB),
                'websiteId' => (int) $website->getId(),
                'websiteCode' => $website->getCode(),
                'storeId' => (int) $group->getId(),
                'storeCode' => $group->getCode(),
                'storeViewId' => (int) $store->getId(),
                'storeViewCode' => $store->getCode(),
                'websiteName' => $website->getName(),
                'storeName' => $group->getName(),
                'storeViewName' => $store->getName(),
                'baseCurrencyCode' => $store->getBaseCurrencyCode(),
                'storeViewCurrencyCode' => $currentCurrencyCode,
                'catalogExtensionVersion' => $this->getCatalogExtensionVersion()
            ];
            $context = $this->jsonSerializer->serialize($contextData);
            $this->cacheInterface->save($context, $cacheId);
        } else {
            $contextData = $this->jsonSerializer->unserialize($context);
        }

        $contextData['environmentId'] = $this->servicesConfig->getEnvironmentId();
        $contextData['environment'] = $this->servicesConfig->getEnvironmentType();
        $contextData['storefrontTemplate'] = 'Luma';
        return $this->jsonSerializer->serialize($contextData);
    }

    /**
     * Return magento-extension version for data services events
     *
     * @return string
     */
    public function getExtensionVersion(): string
    {
        return $this->config->getValue(self::EXTENSION_VERSION_CONFIG_PATH);
    }

    /**
     * Return catalog extension version if installed
     *
     * @return string|null
     */
    private function getCatalogExtensionVersion(): ?string
    {
        $catalogVersion = $this->cacheInterface->load(self::CATALOG_EXPORTER_VERSION_CACHE_KEY);
        if (null == $catalogVersion) {
            $catalogVersion = $this->versionFinder->getVersionFromComposer(self::CATALOG_EXPORTER_PACKAGE_NAME)
                ?? 'undefined';
            $this->cacheInterface->save(
                $catalogVersion, self::CATALOG_EXPORTER_VERSION_CACHE_KEY,
                self::CATALOG_EXPORTER_VERSION_CACHE_TAGS
            );
        }
        return $catalogVersion;
    }

    /**
     * @deprecad since 7.1.0
     * @see getCustomerEmail
     * Return Customer Email address
     *
     * @return string
     */
    public function getCustomerEmailAddress(): string
    {
        return $this->customerSession->getCustomer()->getEmail();
    }

    /**
     * Returns Customer Email address.
     * Returns NULL if customer is not logged in.
     *
     * @return string|null
     */
    public function getCustomerEmail(): ?string
    {
        if (!$this->accountEmail) {
            $this->accountEmail = $this->customerSession->isLoggedIn()
                ? $this->customerSession->getCustomer()->getEmail()
                : null;
        }
        return $this->accountEmail;
    }

    /**
     * Return cookie restriction mode value.
     *
     * @return string|null
     */
    public function isCookieRestrictionModeEnabled()
    {
        return $this->cookieHelper->isCookieRestrictionModeEnabled();
    }

    /**
     * Check if DataServices functionality should be enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->servicesConfig->isApiKeySet() && $this->servicesConfig->getEnvironmentId();
    }
}
