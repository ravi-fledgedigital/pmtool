<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Vaimo\OTScene7Integration\Api\Data\Scene7AssetInterface;

interface Scene7ImageAssetProviderInterface
{
    /**
     * Generate Scene 7 Asset object, that contains url and size of an Image
     *
     * @param ProductInterface $product
     * @param string $imageId
     * @return Scene7AssetInterface
     */
    public function getAsset(ProductInterface $product, string $imageId): Scene7AssetInterface;

    public function getAssetByFilename(?string $fileName, string $imageId): Scene7AssetInterface;

    /**
     * @param ProductInterface $product
     * @return string[]
     */
    public function getProductAvailableImages(ProductInterface $product): array;
}
