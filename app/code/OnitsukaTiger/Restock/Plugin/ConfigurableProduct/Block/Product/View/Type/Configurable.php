<?php

namespace OnitsukaTiger\Restock\Plugin\ConfigurableProduct\Block\Product\View\Type;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as MagentoConfigurableBlock;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;

class Configurable
{
    /**
     * @var EncoderInterface
     */
    private $jsonEncoder;

    /**
     * @var DecoderInterface
     */
    private $jsonDecoder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $stockState;

    /**
     * Configurable constructor.
     *
     * @param EncoderInterface $jsonEncoder
     * @param DecoderInterface $jsonDecoder
     * @param \Magento\CatalogInventory\Api\StockStateInterface $stockState
     */
    public function __construct(
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState
    ) {
        $this->jsonDecoder = $jsonDecoder;
        $this->jsonEncoder = $jsonEncoder;
        $this->storeManager = $storeManager;
        $this->stockState = $stockState;
    }

    /**
     * After get json config
     *
     * @param MagentoConfigurableBlock $block
     * @param string $config
     * @return string
     * @throws LocalizedException
     */
    public function afterGetJsonConfig(MagentoConfigurableBlock $block, $config)
    {
        $fullActionName = $block->getRequest()->getFullActionName();
        $configArray = $this->jsonDecoder->decode($config);
        $productsData = $productsStockStatus = $restockData = $restockDataSize = $colorOptionProductId = [];

        /*if ($fullActionName == 'catalog_product_view') {
            $childProducts = $block->getAllowProducts();

            foreach ($childProducts as $childProduct) {
                $productId = $childProduct->getId();
                $stocksQty  = $this->stockState->getStockQty($childProduct->getId(), $childProduct->getStore()->getWebsiteId());
                if ($stocksQty == 0) {
                    if ($childProduct->getRestockNotificationFlag() &&  $childProduct->getRestockNotificationFlag() == 2) {
                        $productsData[$childProduct->getQaSize()] = $productId;
                        $restockDataSize[][$childProduct->getColorCode()] = $childProduct->getQaSize();
                        $colorOptionProductId[][$childProduct->getQaSize()] = $childProduct->getColorCode() . '-' . $productId;
                    } else {
                        $restockData[$childProduct->getColorCode()] = $productId;
                        $productsStockStatus[][$childProduct->getColorCode()] = $childProduct->getQaSize();*/

                        /*if ($childProduct->getRestockNotificationFlag() != 2) {
                            if ($stocksQty == 0) {
                                $restockData[$childProduct->getColorCode()] = $productId;
                                $productsStockStatus[][$childProduct->getColorCode()] = $childProduct->getQaSize();
                            }
                        }*/
                    /*}
                }
            }
        }*/

        $storeCode = $this->storeManager->getStore()->getCode();
        $configArray['restock_product_data'] = $productsData;
        $configArray['currentStoreCode'] = $storeCode;
        $configArray['product_status'] = $productsStockStatus;
        $configArray['restock_data'] = $restockData;
        $configArray['restock_data_size'] = $restockDataSize;
        $configArray['color_option_product'] = $colorOptionProductId;

        $config = $this->jsonEncoder->encode($configArray);

        return $config;
    }
}
