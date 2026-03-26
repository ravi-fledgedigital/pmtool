<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Plugin\Swatches\Helper;

use Magento\Catalog\Model\Product as ModelProduct;
use Magento\Swatches\Helper\Data as Subject;
use Vaimo\OTScene7Integration\Api\Scene7ImageAssetProviderInterface;
use Vaimo\OTScene7Integration\Model\ConfigProvider;

class DataPlugin
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

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @param ModelProduct $product
     * @return mixed[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetProductMediaGallery(Subject $subject, callable $proceed, ModelProduct $product): array
    {
        if (!$this->configProvider->isEnabled()) {
            return $proceed($product);
        }

        return [
            'isMain' => true,
            'position' => 1,
            'large' => $this->assetProvider->getAsset($product, 'product_swatch_image_large')->getUrl(),
            'medium' => $this->assetProvider->getAsset($product, 'product_swatch_image_medium')->getUrl(),
            'small' => $this->assetProvider->getAsset($product, 'product_swatch_image_small')->getUrl(),
        ];
    }

    /**
     * @param Subject $subject
     * @param mixed[] $result
     * @param ModelProduct $product
     * @return mixed[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetProductMediaGallery(Subject $subject, array $result, ModelProduct $product): array
    {
        $mouseoverAsset = $this->assetProvider->getAsset(
            $product,
            'category_page_grid_base_second'
        );

        if ($mouseoverAsset->isPlaceHolder()) {
            $mouseoverAsset = $this->assetProvider->getAsset(
                $product,
                'category_page_grid_base'
            );
        }

        $result['base_mouseover_image'] = $mouseoverAsset->getUrl();

        return $result;
    }
}
