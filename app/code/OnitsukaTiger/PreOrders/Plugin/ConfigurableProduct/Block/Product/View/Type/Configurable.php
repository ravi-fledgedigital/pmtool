<?php
/**
 * Copyright © Adobe. All rights reserved.
 */
namespace OnitsukaTiger\PreOrders\Plugin\ConfigurableProduct\Block\Product\View\Type;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as MagentoConfigurableBlock;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Exception\LocalizedException;
use OnitsukaTiger\PreOrders\Helper\Data;
use OnitsukaTiger\PreOrders\Helper\PreOrder;

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
     * @var Data
     */
    protected $dateHelper;

    /**
     * @var PreOrder
     */
    protected $preOrder;

    /**
     * Configurable constructor.
     *
     * @param EncoderInterface $jsonEncoder
     * @param DecoderInterface $jsonDecoder
     * @param Data $dateHelper
     * @param PreOrder $preOrder
     */
    public function __construct(
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder,
        Data $dateHelper,
        PreOrder $preOrder
    ) {
        $this->jsonDecoder = $jsonDecoder;
        $this->jsonEncoder = $jsonEncoder;
        $this->dateHelper = $dateHelper;
        $this->preOrder = $preOrder;
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
        if($this->dateHelper->isModuleEnabled()){
            $configArray = $this->jsonDecoder->decode($config);
            $productsData = [];
            $childProducts = $block->getAllowProducts();
            $currentDate = date('Y-m-d');
            foreach ($childProducts as $childProduct) {
                $stockMessage = '';
                $buttonLabel = '';
                $isPreOrder = false;
                $productId = $childProduct->getId();

                if($this->preOrder->isProductPreOrder($productId)){
                    $stockMessage = $this->preOrder->getPreOrderStatusLabelByProductId($productId);
                    $buttonLabel = __('Pre-Order');
                    $isPreOrder = true;
                } else {
                    $stockMessage = __('In Stock');
                    $buttonLabel = __('Add to Cart');
                }
                $productsData[$productId] = [
                    'stockMessage' => $stockMessage,
                    'buttonTitle' => $buttonLabel,
                    'isPreOrder' => $isPreOrder
                ];
            }

            $configArray['onitsukatiger_preorders_product'] = $productsData;
            $config = $this->jsonEncoder->encode($configArray);
        }
        
        return $config;
    }
}
