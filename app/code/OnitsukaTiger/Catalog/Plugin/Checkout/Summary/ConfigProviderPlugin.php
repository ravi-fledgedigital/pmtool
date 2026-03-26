<?php

declare(strict_types=1);

namespace OnitsukaTiger\Catalog\Plugin\Checkout\Summary;

use Magento\Catalog\Api\ProductRepositoryInterface;
use OnitsukaTiger\Catalog\Helper\Data;
use OnitsukaTiger\Catalog\Model\Product;

/**
 * Class Helper Data
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigProviderPlugin extends \Magento\Framework\Model\AbstractModel
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
        Data                       $helperData
    )
    {
        $this->productRepository = $productRepository;
        $this->helperData = $helperData;
    }

    /**
     * @param \Magento\Checkout\Model\DefaultConfigProvider $subject
     * @param array $result
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException\
     */
    public function afterGetConfig(\Magento\Checkout\Model\DefaultConfigProvider $subject, array $result): array
    {
        if (isset($result['quoteItemData']) && isset($result['totalsData']['items'])) {
            foreach ($result['totalsData']['items'] as $key => $item) {
                foreach ($result['quoteItemData'] as $quoteItem) {
                    if ($quoteItem['item_id'] == $item['item_id'] && isset($item['options'])) {
                        $product = $this->productRepository->get($quoteItem['sku']);
                        $color = $product->getResource()->getAttribute(Product::COLOR);
                        $result['totalsData']['items'][$key]['color'] = $color->getSource()->getOptionText($product->getColor());
                    }
                }
            }
        }
        return $result;
    }
}
