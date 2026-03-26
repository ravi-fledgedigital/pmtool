<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PageBuilderProductRecommendations\Block;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Asset\Repository;
use Magento\Catalog\Helper\ImageFactory;
use Magento\ProductRecommendationsLayout\Block\Renderer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class PageBuilderRecommendation
 * @package Magento\PageBuilderProductRecommendations\Block
 */
class PageBuilderRecommendation extends Renderer
{
    protected $_template = 'Magento_PageBuilderProductRecommendations::pageBuilderRenderer.phtml';

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
     * @param Context $context
     * @param Repository $assetRepo
     * @param ImageFactory $helperImageFactory
     * @param StoreManagerInterface $storeManager
     * @param ResolverInterface $localeResolver
     * @param Json $serializer
     */
    public function __construct(
        Context $context,
        Repository $assetRepo,
        ImageFactory $helperImageFactory,
        StoreManagerInterface $storeManager,
        ResolverInterface $localeResolver,
        Json $serializer
    ) {
        $this->assetRepo = $assetRepo;
        $this->helperImageFactory = $helperImageFactory;
        $this->storeManager = $storeManager;
        $this->localeResolver = $localeResolver;
        $this->serializer = $serializer;
        parent::__construct($context, $assetRepo, $helperImageFactory, $storeManager, $localeResolver,  $serializer);
    }

    /**
     * Get Default StoreViewCode
     *
     * @return string
     */
    public function getDefaultStoreViewCode(): string
    {
        $storeId = $this->getData('store_id');
        if ($storeId) {
            // If store_id is provided, we don't need default store view code
            // as recommendation service can use the specific store view code
            return '';
        }
        return $this->storeManager->getDefaultStoreView()->getCode();
    }

    /**
     * Get store view code for the current store
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getStoreViewCode(): ?string
    {
        $storeId = $this->getData('store_id');
        if ($storeId) {
            $store = $this->storeManager->getStore($storeId);
            return $store->getCode();
        }
        return null;
    }

    /**
     * Get store code for the current store
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getStoreCode(): ?string
    {
        $storeId = $this->getData('store_id');
        if ($storeId) {
            $store = $this->storeManager->getStore($storeId);
            $storeGroup = $this->storeManager->getGroup($store->getGroupId());
            return $storeGroup->getCode();
        }
        return null;
    }
}
