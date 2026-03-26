<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Block\Product\Renderer;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Product as CatalogProduct;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\ConfigurableProduct\Helper\Data;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Swatches\Helper\Data as SwatchData;
use Magento\Swatches\Helper\Media;
use Magento\Swatches\Model\SwatchAttributesProvider;
use Vaimo\OTScene7Integration\Api\Scene7ImageAssetProviderInterface;
use Vaimo\OTScene7Integration\Model\ConfigProvider;

class Configurable extends \Magento\Swatches\Block\Product\Renderer\Configurable
{
    use VariationMediaTrait;

    private ConfigProvider $configProvider;
    private Scene7ImageAssetProviderInterface $assetProvider;
    private ?SwatchAttributesProvider $swatchAttributesProvider;

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
     * @param UrlBuilder|null $imageUrlBuilder
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
        ?UrlBuilder $imageUrlBuilder = null
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
            $imageUrlBuilder
        );
        $this->configProvider = $configProvider;
        $this->assetProvider = $assetProvider;
        $this->swatchAttributesProvider = $swatchAttributesProvider
            ?: ObjectManager::getInstance()->get(SwatchAttributesProvider::class);
    }

    /**
     * @return mixed[]
     */
    // phpcs:ignore VCQP.PHP.ProtectedClassMember.FoundProtected
    protected function getOptionImages(): array
    {
        if (!$this->configProvider->isEnabled()) {
            return parent::getOptionImages();
        }

        $images = [];
        foreach ($this->getAllowProducts() as $product) {
            $files = $this->assetProvider->getProductAvailableImages($product);
            if (empty($files)) {
                $files[] = null;
            }

            $position = 0;
            if (
                (str_contains($product->getSku(), '1182A676.200') || str_contains($product->getSku(), '1182A676.100') || str_contains($product->getSku(), '1182A676.001'))
                && isset($files["SB_FR"]) && isset($files["SB_Z1"]) && isset($files["SB_Z2"])) {
                $files = $this->moveArrayElementPostion($files);
            }

            foreach ($files as $fileName) {
                $images[$product->getId()][] = [
                    'fullImageUrl' => $this->assetProvider->getAssetByFilename(
                        $fileName,
                        'product_page_image_large'
                    )->getUrl(),
                    'thumb' => $this->assetProvider->getAssetByFilename(
                        $fileName,
                        'product_page_image_small',
                    )->getUrl(),
                    'img' => $this->assetProvider->getAssetByFilename(
                        $fileName,
                        'product_page_image_medium',
                    )->getUrl(),
                    'caption' => $product->getName(),
                    'position' => ++$position,
                    'isMain' => $position === 1,
                    'type' => 'image',
                ];
            }
        }

        return $images;
    }

    private function moveArrayElementPostion($availableImages)
    {
        $keyToMoveFirst = 'SB_FR';
        $keyToMoveSecond = 'SB_Z1';
        $keyToMoveThird = 'SB_Z2';
        $elementToMove = [$keyToMoveFirst => $availableImages[$keyToMoveFirst], $keyToMoveSecond => $availableImages[$keyToMoveSecond], $keyToMoveThird => $availableImages[$keyToMoveThird]]; // Store the key-value pair

        unset($availableImages[$keyToMoveFirst]);
        unset($availableImages[$keyToMoveSecond]);
        unset($availableImages[$keyToMoveThird]);

        $newArray = [];
        $targetPosition = 1;
        $counter = 0;

        foreach ($availableImages as $key => $value) {
            if ($counter === $targetPosition) {
                $newArray = array_merge($newArray, $elementToMove);
            }
            $newArray[$key] = $value;
            $counter++;
        }

        if ($targetPosition >= count($availableImages)) {
            $newArray = array_merge($newArray, $elementToMove);
        }

        return $newArray;
    }
}
