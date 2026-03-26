<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Vaimo\OTScene7Integration\Api\Scene7ImageAssetProviderInterface;
use Vaimo\OTScene7Integration\Model\ConfigProvider;

class Data extends AbstractHelper
{
    private ConfigProvider $configProvider;
    private Scene7ImageAssetProviderInterface $assetProvider;
    private \Magento\Catalog\Helper\Image $image;
    private \Magento\Catalog\Api\ProductRepositoryInterface $productRepository;
    private \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository;

    /**
     * @param Context $context
     * @param ConfigProvider $configProvider
     * @param Scene7ImageAssetProviderInterface $assetProvider
     * @param \Magento\Catalog\Helper\Image $image
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
     */
    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        Scene7ImageAssetProviderInterface $assetProvider,
        \Magento\Catalog\Helper\Image $image,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
    ) {
        parent::__construct($context);
        $this->configProvider = $configProvider;
        $this->assetProvider = $assetProvider;
        $this->image = $image;
        $this->productRepository = $productRepository;
        $this->orderItemRepository = $orderItemRepository;
    }

    /**
     * * Returns url of product image to use in order items lists. This is only used for OT theme template files.
     *
     * @param  $product
     * @return string|null
     */
    public function getOrderItemProductImage($product, $typeImageSize = 'order_item_image_small'): ?string
    {
        if (!$product) {
            return '';
        }

        if (!$this->configProvider->isEnabled()) {
            return $this->image->init($product, 'product_page_image_small')
                ->setImageFile($product->getImage())->resize(100, 100)->getUrl();
        }

        return $this->assetProvider->getAsset($product, $typeImageSize)->getUrl();
    }


    /**
     * @param $orderItemId
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getImageUrlByProductSku($orderItemId)
    {
        $orderItem = $this->orderItemRepository->get($orderItemId);
        $product = $this->productRepository->getById($orderItem->getProductId());
        if (!$this->configProvider->isEnabled()) {
            return null;
        }

        return $this->assetProvider->getAsset($product, 'order_item_image_small')->getUrl();
    }
}
