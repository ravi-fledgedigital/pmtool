<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Block\Product\Renderer;

use Magento\Catalog\Model\Product;
use Magento\Swatches\Model\Swatch;

trait VariationMediaTrait
{
    /**
     * @param Product $childProduct
     * @param string $imageType
     * @return string
     */
    protected function getSwatchProductImage(Product $childProduct, $imageType): string
    {
        if (!$this->configProvider->isEnabled()) {
            return parent::getSwatchProductImage($childProduct, $imageType);
        }

        return $this->assetProvider->getAsset($childProduct, $imageType)->getUrl();
    }

    /**
     * @param string $attributeCode
     * @param string $optionId
     * @return string[]
     */
    protected function getVariationMedia($attributeCode, $optionId): array
    {
        if (!$this->configProvider->isEnabled()) {
            return parent::getVariationMedia($attributeCode, $optionId);
        }

        $variationProduct = $this->getFirstVariationWithImages(
            $this->getProduct(),
            [$attributeCode => $optionId]
        );

        $variationMediaArray = [];
        if ($variationProduct) {
            $variationMediaArray = [
                'value' => $this->getSwatchProductImage($variationProduct, Swatch::SWATCH_IMAGE_NAME),
                'thumb' => $this->getSwatchProductImage($variationProduct, Swatch::SWATCH_THUMBNAIL_NAME),
            ];
        }

        return $variationMediaArray;
    }

    /**
     * @param Product $configurableProduct
     * @param string[] $requiredAttributes
     * @return Product|null
     */
    private function getFirstVariationWithImages(Product $configurableProduct, array $requiredAttributes): ?Product
    {
        if (empty($this->swatchAttributesProvider->provide($configurableProduct))) {
            return null;
        }

        $usedProducts = $configurableProduct->getTypeInstance()->getUsedProducts($configurableProduct);

        foreach ($usedProducts as $simpleProduct) {
            if (array_diff_assoc($requiredAttributes, $simpleProduct->getData())) {
                continue;
            }

            if (empty($simpleProduct->getData('scene7_available_image_angles'))) {
                continue;
            }

            return $simpleProduct;
        }

        return null;
    }
}
