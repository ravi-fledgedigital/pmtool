<?php

declare(strict_types=1);

namespace OnitsukaTiger\Catalog\Plugin\Checkout\CustomerData;

use Magento\Catalog\Api\ProductRepositoryInterface;
use OnitsukaTiger\Catalog\Helper\Data;
use OnitsukaTiger\Catalog\Model\Product;

/**
 * Class Helper Data
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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
    public function afterGetItemData(\Magento\Checkout\CustomerData\AbstractItem $subject, array $result): array
    {
        $product = $this->productRepository->get($result['product_sku']);
        $color = $product->getResource()->getAttribute(Product::COLOR);
        $colorValue = $color->getSource()->getOptionText($product->getData(Product::COLOR));
        if ($colorValue) {
            $result[Product::COLOR] = $colorValue;
        }
        return $result;
    }
}
