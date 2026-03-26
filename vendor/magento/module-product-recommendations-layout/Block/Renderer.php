<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductRecommendationsLayout\Block;

use Magento\Catalog\Helper\ImageFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Locale\FormatInterface;

class Renderer extends Template
{
    /**
     * Config Paths
     * @var string
     */
    const CONFIG_PATH_ALTERNATE_ENVIRONMENT_ENABLED = 'services_connector/product_recommendations/alternate_environment_enabled';
    const CONFIG_PATH_ALTERNATE_ENVIRONMENT_ID = 'services_connector/product_recommendations/alternate_environment_id';

    /**
     * @var Repository
     */
    private $assetRepo;

    /**
     * @var ImageFactory
     */
    private $helperImageFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var FormatInterface
     */
    protected $localeFormat;

    /**
     * @param Context $context
     * @param Repository $assetRepo
     * @param ImageFactory $helperImageFactory
     * @param StoreManagerInterface $storeManager
     * @param ResolverInterface $localeResolver
     * @param Json $serializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        Repository $assetRepo,
        ImageFactory $helperImageFactory,
        StoreManagerInterface $storeManager,
        ResolverInterface $localeResolver,
        Json $serializer,
        array $data = [],
        ?FormatInterface $localeFormat = null
    ) {
        $this->assetRepo = $assetRepo;
        $this->helperImageFactory = $helperImageFactory;
        $this->storeManager = $storeManager;
        $this->localeResolver = $localeResolver;
        $this->serializer = $serializer;
        $this->localeFormat = $localeFormat ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(FormatInterface::class);
        parent::__construct($context, $data);
    }

    /**
     * Get place holder image of a product for small_image
     *
     * @param string $imageType
     * @return string
     * @throws NoSuchEntityException
     */
    public function getPlaceholderUrl(string $imageType): string
    {
        $configPath = 'catalog/placeholder/' . $imageType . '_placeholder';
        $relativePlaceholderUrl = $this->_scopeConfig->getValue($configPath);

        if (!$relativePlaceholderUrl) {
            $imageHelper = $this->helperImageFactory->create();
            $url = $imageHelper->getDefaultPlaceholderUrl($imageType);
        } else {
            $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            $url = $baseUrl . 'catalog/product/placeholder/' . $relativePlaceholderUrl;
        }

        return $url;
    }

    /**
     * Get current system locale for correct price converting
     *
     * @deprecated
     * @return string
     */
    public function getSystemLocale(): string
    {
        return str_replace("_", "-", $this->localeResolver->getLocale());
    }

    /**
     * Get Current system's Price format
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getPriceFormat()
    {
        return json_encode(
            $this->localeFormat->getPriceFormat()
        );
    }

    /**
     * Get currency configuration
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCurrencyConfiguration(): string
    {
        $store = $this->storeManager->getStore();
        $currentCurrency = $store->getCurrentCurrency()->getCurrencyCode();
        return $this->serializer->serialize([
            'currency' => $currentCurrency,
            'rate' => $store->getBaseCurrency()->getRate($currentCurrency)
        ]);
    }

    /**
     * Check if alternate environment is being used to fetch recommendations
     *
     * @return bool
     */
    public function isAlternateEnvironmentEnabled(): bool
    {
        return (bool) $this->_scopeConfig->getValue(self::CONFIG_PATH_ALTERNATE_ENVIRONMENT_ENABLED);
    }

    /**
     * Get alternate environment id to fetch recommendations
     *
     * @return string
     */
    public function getAlternateEnvironmentId(): string
    {
        $alternateEnvironmentId = "";
        if ($this->_scopeConfig->getValue(self::CONFIG_PATH_ALTERNATE_ENVIRONMENT_ENABLED)) {
            $alternateEnvironmentId = $this->_scopeConfig->getValue(self::CONFIG_PATH_ALTERNATE_ENVIRONMENT_ID);
        }
        return $alternateEnvironmentId;
    }
}
