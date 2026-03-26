<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Order;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

class OrderItemImage
{
    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    private $imageHelper;

    public function __construct(
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Helper\Image $imageHelper
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
    }

    /**
     * @param int $orderItemId
     * @param string $imageId
     *
     * @return string
     */
    public function getUrl($orderItemId, $imageId = 'product_thumbnail_image')
    {
        if (!$orderItemId) {
            return '';
        }

        try {
            $orderItem = $this->orderItemRepository->get($orderItemId);
        } catch (\Exception $e) {
            return $this->imageHelper->getDefaultPlaceholderUrl('small_image');
        }

        try {
            $product = $this->productRepository->getById($orderItem->getProductId());
            if ($product->getMediaGalleryEntries()) {
                return $this->imageHelper->init($product, $imageId)->getUrl();
            }
        } catch (NoSuchEntityException $e) {
            null;
        }

        if (!empty($orderItem->getParentItemId())) {
            try {
                $orderItem = $this->orderItemRepository->get($orderItem->getParentItemId());
            } catch (\Exception $e) {
                return $this->imageHelper->getDefaultPlaceholderUrl('small_image');
            }

            try {
                $product = $this->productRepository->getById($orderItem->getProductId());
                if ($product->getMediaGalleryEntries()) {
                    return $this->imageHelper->init($product, $imageId)->getUrl();
                }
            } catch (NoSuchEntityException $e) {
                null;
            }
        }

        return $this->imageHelper->getDefaultPlaceholderUrl('small_image');
    }
}
