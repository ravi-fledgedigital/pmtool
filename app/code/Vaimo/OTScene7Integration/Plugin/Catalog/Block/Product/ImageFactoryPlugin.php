<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Plugin\Catalog\Block\Product;

use Magento\Catalog\Block\Product\Image as ImageBlock;
use Magento\Catalog\Block\Product\ImageFactory as Subject;
use Magento\Catalog\Model\Product;
use Magento\Framework\ObjectManagerInterface;
use Vaimo\OTScene7Integration\Api\Scene7ImageAssetProviderInterface;
use Vaimo\OTScene7Integration\Model\ConfigProvider;

class ImageFactoryPlugin
{
    private ConfigProvider $configProvider;
    private Scene7ImageAssetProviderInterface $assetProvider;
    private ObjectManagerInterface $objectManager;

    public function __construct(
        ConfigProvider $configProvider,
        Scene7ImageAssetProviderInterface $assetProvider,
        ObjectManagerInterface $objectManager
    ) {
        $this->configProvider = $configProvider;
        $this->assetProvider = $assetProvider;
        $this->objectManager = $objectManager;
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @param Product $product
     * @param string $imageId
     * @param string[]|null $attributes
     * @return ImageBlock
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCreate(
        Subject $subject,
        callable $proceed,
        Product $product,
        string $imageId,
        // phpcs:ignore SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue.NullabilityTypeMissing
        array $attributes = null
    ): ImageBlock {
        if (!$this->configProvider->isEnabled()) {
            return $proceed($product, $imageId, $attributes);
        }

        $asset = $this->assetProvider->getAsset($product, $imageId);
        $data = [
            'data' => [
                'template' => 'Magento_Catalog::product/image_with_borders.phtml',
                'image_url' => $asset->getUrl(),
                'width' => $asset->getWidth(),
                'height' => $asset->getHeight(),
                'is_placeholder' => $asset->isPlaceHolder(),
                'label' => $product->getName(),
                'ratio' => $this->getRatio($asset->getWidth(), $asset->getWidth()),
                'custom_attributes' => explode(",", $this->getStringCustomAttributes($attributes)),
                'class' => $attributes['class'] ?? 'product-image-photo',
                'product_id' => $product->getId(),
            ],
        ];

        // phpcs:ignore MEQP2.Classes.ObjectManager.ObjectManagerFound
        return $this->objectManager->create(ImageBlock::class, $data);
    }

    /**
     * Retrieve image custom attributes for HTML element
     *
     * @param string[] $attributes
     * @return string
     */
    private function getStringCustomAttributes(array $attributes): string
    {
        $result = [];
        foreach ($attributes as $name => $value) {
            if ($name == 'class') {
                continue;
            }

            $result[] = $name . '="' . $value . '"';
        }

        return !empty($result) ? \implode(' ', $result) : '';
    }

    /**
     * Calculate image ratio
     *
     * @param int $width
     * @param int $height
     * @return float
     */
    private function getRatio(int $width, int $height): float
    {
        if ($width && $height) {
            return $height / $width;
        }

        return 1.0;
    }
}
