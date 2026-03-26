<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Plugin\Catalog;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface as Subject;
use Vaimo\OTScene7Integration\Api\Scene7ImageAssetProviderInterface;
use Vaimo\OTScene7Integration\Model\ConfigProvider;

class ProductRepositoryPlugin
{
    private const ATTRIBUTES_TO_REPLACE = [
        'image',
        'thumbnail',
        'small_image',
    ];

    private ConfigProvider $configProvider;
    private Scene7ImageAssetProviderInterface $assetProvider;

    public function __construct(
        ConfigProvider $configProvider,
        Scene7ImageAssetProviderInterface $assetProvider
    ) {
        $this->configProvider = $configProvider;
        $this->assetProvider = $assetProvider;
    }

    public function afterGetById(Subject $productRepository, ProductInterface $product): ProductInterface
    {
        if (!$this->configProvider->isEnabled()) {
            return $product;
        }

        foreach (self::ATTRIBUTES_TO_REPLACE as $attributeCode) {
            $asset = $this->assetProvider->getAsset($product, $attributeCode);
            $product->setData($attributeCode, $asset->getUrl());
        }

        return $product;
    }
}
