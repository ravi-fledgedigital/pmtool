<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTAdobeDataLayer\ViewModel;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Vaimo\OTAdobeDataLayer\Api\ConfigInterface;
// use Vaimo\ScriptToBottomExclusion\Api\ScriptModifierInterface;

class AdobeDataLayer implements ArgumentInterface
{
    private const USER_INFO_API_URL = 'rest/V1/data-layer';

    private ConfigInterface $dataLayerConfig;
    // private ScriptModifierInterface $scriptModifier;
    private SerializerInterface $serializer;
    private UrlInterface $urlBuilder;

    /**
     * @var array<string, string> $dataLayerComponents
     */
    private array $dataLayerComponents;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @param array<string, string> $dataLayerComponents
     */
    public function __construct(
        ConfigInterface $dataLayerConfig,
        // ScriptModifierInterface $scriptModifier,
        SerializerInterface $serializer,
        UrlInterface $urlBuilder,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Registry $registry,
        array $dataLayerComponents = []
    ) {
        $this->dataLayerConfig = $dataLayerConfig;
        // $this->scriptModifier = $scriptModifier;
        $this->serializer = $serializer;
        $this->urlBuilder = $urlBuilder;
        $this->dataLayerComponents = $dataLayerComponents;
        $this->request = $request;
        $this->registry = $registry;
    }

    public function addMoveExclusionAttribute(string $script): string
    {
        // return $this->scriptModifier->addMoveExclusionAttribute($script);
        return '';
    }

    public function getLaunchEmbedCode(): ?string
    {
        //return $this->addMoveExclusionAttribute($this->dataLayerConfig->getLaunchEmbedCode());
        return $this->dataLayerConfig->getLaunchEmbedCode();
    }

    public function getUserInfoUrl(): string
    {
        return $this->urlBuilder->getDirectUrl(self::USER_INFO_API_URL);
    }

    public function getDataLayerComponents(): string
    {
        $currentPage = $this->getCurrentPage();

        if(isset($this->dataLayerComponents['pageType']) && !empty($currentPage)) {
            $this->dataLayerComponents['pageType'] = $currentPage;
        }

        if($this->request->getFullActionName()) {
            $this->dataLayerComponents['currentPage'] = $this->request->getFullActionName();
        }

        $currentCategory = $this->getCurrentCategory();

        if(isset($this->dataLayerComponents['categoryInfo']) && $currentCategory && $currentCategory->getId()) {
            $this->dataLayerComponents['categoryInfo'] = $currentCategory->getId();
        }

        return $this->serializer->serialize(['requestedComponents' => $this->dataLayerComponents]);
    }

    private function getCurrentPage()
    {

        $fullActionName = $this->request->getFullActionName();
        $pageType = '';
        if ($fullActionName == 'cms_index_index') {
            $pageType = "Home";
        } else if ($fullActionName == 'catalog_category_view') {
            $pageType = "Category";
        } else if ($fullActionName == 'catalog_product_view') {
            $pageType = "Product";
        } else if ($fullActionName == 'catalogsearch_result_index') {
            $pageType = "Search";
        } else if ($fullActionName == 'checkout_cart_index') {
            $pageType = "Basket";
        } else if ($fullActionName == 'checkout_index_index') {
            $pageType = "Checkout";
        } else if ($fullActionName == 'checkout_onepage_success') {
            $pageType = "Confirmation";
        } else if (in_array($fullActionName, ['cms_page_view', 'stores_index_index', 'trackorder_index_index'])) {
            $pageType = "Content";
        }else{
            $pageType = "Cms";
        }

        return $pageType;
    }

    private function getCurrentCategory()
    {
        return $this->registry->registry('current_category');
    }

    private function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }
}
