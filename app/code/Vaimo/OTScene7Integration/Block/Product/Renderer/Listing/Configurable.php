<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Block\Product\Renderer\Listing;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Product as CatalogProduct;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\ConfigurableProduct\Helper\Data;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Variations\Prices;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\Format;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Swatches\Helper\Data as SwatchData;
use Magento\Swatches\Helper\Media;
use Magento\Swatches\Model\SwatchAttributesProvider;
use Vaimo\OTScene7Integration\Api\Scene7ImageAssetProviderInterface;
use Vaimo\OTScene7Integration\Block\Product\Renderer\VariationMediaTrait;
use Vaimo\OTScene7Integration\Model\ConfigProvider;

class Configurable extends \Magento\Swatches\Block\Product\Renderer\Listing\Configurable
{
    use VariationMediaTrait;

    private ConfigProvider $configProvider;
    private Scene7ImageAssetProviderInterface $assetProvider;
    private SwatchAttributesProvider $swatchAttributesProvider;

    /**
     * @param Context $context
     * @param ArrayUtils $arrayUtils
     * @param EncoderInterface $jsonEncoder
     * @param Data $helper
     * @param CatalogProduct $catalogProduct
     * @param CurrentCustomer $currentCustomer
     * @param PriceCurrencyInterface $priceCurrency
     * @param ConfigurableAttributeData $configurableAttributeData
     * @param SwatchData $swatchHelper
     * @param Media $swatchMediaHelper
     * @param ConfigProvider $configProvider
     * @param Scene7ImageAssetProviderInterface $assetProvider
     * @param mixed[] $data
     * @param SwatchAttributesProvider|null $swatchAttributesProvider
     * @param Format|null $localeFormat
     * @param Prices|null $variationPrices
     * @param Resolver|null $layerResolver
     */
    public function __construct(
        Context $context,
        ArrayUtils $arrayUtils,
        EncoderInterface $jsonEncoder,
        Data $helper,
        CatalogProduct $catalogProduct,
        CurrentCustomer $currentCustomer,
        PriceCurrencyInterface $priceCurrency,
        ConfigurableAttributeData $configurableAttributeData,
        SwatchData $swatchHelper,
        Media $swatchMediaHelper,
        ConfigProvider $configProvider,
        Scene7ImageAssetProviderInterface $assetProvider,
        array $data = [],
        ?SwatchAttributesProvider $swatchAttributesProvider = null,
        ?Format $localeFormat = null,
        ?Prices $variationPrices = null,
        ?Resolver $layerResolver = null
    ) {
        parent::__construct(
            $context,
            $arrayUtils,
            $jsonEncoder,
            $helper,
            $catalogProduct,
            $currentCustomer,
            $priceCurrency,
            $configurableAttributeData,
            $swatchHelper,
            $swatchMediaHelper,
            $data,
            $swatchAttributesProvider,
            $localeFormat,
            $variationPrices,
            $layerResolver
        );
        $this->configProvider = $configProvider;
        $this->assetProvider = $assetProvider;
        $this->swatchAttributesProvider = $swatchAttributesProvider
            ?: ObjectManager::getInstance()->get(SwatchAttributesProvider::class);
    }
}
