<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Plugin\Catalog\Block\Product\View;

use Magento\Catalog\Block\Product\View\Gallery as Subject;
use Vaimo\OTScene7Integration\Api\Scene7ImageAssetProviderInterface;
use Vaimo\OTScene7Integration\Model\ConfigProvider;

class GalleryPlugin
{
    private ConfigProvider $configProvider;
    private Scene7ImageAssetProviderInterface $assetProvider;

    public function __construct(
        ConfigProvider $configProvider,
        Scene7ImageAssetProviderInterface $assetProvider
    ) {
        $this->configProvider = $configProvider;
        $this->assetProvider = $assetProvider;
    }

    public function aroundGetGalleryImagesJson(Subject $subject, callable $proceed): string
    {
        if (!$this->configProvider->isEnabled()) {
            return $proceed();
        }

        $product = $subject->getProduct();
        $imagesItems = [];
        $files = $this->assetProvider->getProductAvailableImages($product);

        if (empty($files)) {
            $files[] = null;
        }

        $position = 0;
        foreach ($files as $fileName) {
            $imagesItems[] = [
                'thumb' => $this->assetProvider->getAssetByFilename(
                    $fileName,
                    'product_page_image_small'
                )->getUrl(),
                'img' => $this->assetProvider->getAssetByFilename(
                    $fileName,
                    'product_page_image_medium',
                )->getUrl(),
                'full' => $this->assetProvider->getAssetByFilename(
                    $fileName,
                    'product_page_image_large',
                )->getUrl(),
                'caption' => $product->getName(),
                'position' => ++$position,
                'isMain' => $position === 1,
                'type' => 'image',
            ];
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction.DiscouragedWithAlternative
        return \json_encode($imagesItems);
    }
}
