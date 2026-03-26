<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Plugin\Checkout\CustomerData;

use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use Magento\Checkout\CustomerData\ItemInterface;
use Magento\Quote\Model\Quote\Item;
use Vaimo\OTScene7Integration\Api\Scene7ImageAssetProviderInterface;
use Vaimo\OTScene7Integration\Model\ConfigProvider;

class ItemPlugin
{
    private ItemResolverInterface $itemResolver;
    private ConfigProvider $configProvider;
    private Scene7ImageAssetProviderInterface $assetProvider;

    public function __construct(
        ConfigProvider $configProvider,
        ItemResolverInterface $itemResolver,
        Scene7ImageAssetProviderInterface $assetProvider
    ) {
        $this->itemResolver = $itemResolver;
        $this->configProvider = $configProvider;
        $this->assetProvider = $assetProvider;
    }

    /**
     * @param ItemInterface $subject
     * @param mixed[] $result
     * @param Item $item
     * @return mixed[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetItemData(ItemInterface $subject, array $result, Item $item): array
    {
        if (!$this->configProvider->isEnabled()) {
            return $result;
        }

        if (empty($item->getChildren())) {
            $product = $this->itemResolver->getFinalProduct($item);
        } else {
            $product = $item->getChildren()[0]->getProduct();
        }

        $asset = $this->assetProvider->getAsset($product, 'mini_cart_product_thumbnail');

        $result['product_image'] = [
            'src' => $asset->getUrl(),
            'alt' => $product->getName(),
            'width' => $asset->getWidth(),
            'height' => $asset->getHeight(),
        ];

        return $result;
    }
}
