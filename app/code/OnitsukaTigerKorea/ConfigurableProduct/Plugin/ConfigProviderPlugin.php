<?php

namespace OnitsukaTigerKorea\ConfigurableProduct\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use OnitsukaTigerKorea\ConfigurableProduct\Helper\Data;

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
    public function afterGetConfig(\Magento\Checkout\Model\DefaultConfigProvider $subject, array $result)
    {
        if (isset($result['quoteItemData']) && isset($result['totalsData']['items'])) {
            foreach ($result['totalsData']['items'] as $key => $item) {
                foreach ($result['quoteItemData'] as $quoteItem) {
                    if ($quoteItem['item_id'] == $item['item_id'] && isset($item['options'])) {
                        $product = $this->productRepository->get($quoteItem['sku']);
                        if ($this->helperData->enableShowSizeForDisplay($quoteItem['store_id']) && $product->getSizeForDisplay()) {
                            $result['totalsData']['items'][$key]['size_for_display'] = $product->getsizeForDisplay();
                        }
                    }
                }
            }
        }
        return $result;
    }
}
