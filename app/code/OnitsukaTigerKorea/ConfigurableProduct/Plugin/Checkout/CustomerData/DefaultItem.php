<?php

namespace OnitsukaTigerKorea\ConfigurableProduct\Plugin\Checkout\CustomerData;

use Magento\Catalog\Api\ProductRepositoryInterface;
use OnitsukaTigerKorea\ConfigurableProduct\Helper\Data;

class DefaultItem
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param Data $helperData
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Data $helperData
    ) {
        $this->productRepository = $productRepository;
        $this->helperData = $helperData;
    }

    /**
     * @param \Magento\Checkout\CustomerData\AbstractItem $subject
     * @param array $result
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetItemData(
        \Magento\Checkout\CustomerData\AbstractItem $subject, array $result
    ) {
        $product = $this->productRepository->get($result['product_sku']);
        $sizeForDisplay = $this->helperData->getSizeForDisplay($result['product_sku'], $product->getStoreId());
        if ($sizeForDisplay) {
            $result['size_for_display'] = $sizeForDisplay;
        }
        return $result;
    }
}
