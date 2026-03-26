<?php

namespace OnitsukaTigerKorea\Checkout\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\LayoutInterface;

class ConfigProviderMobile implements ConfigProviderInterface
{
    /**
     * @var LayoutInterface
     */
    protected $_layout;
    /**
     * @var string
     */
    protected $cmsBlock;
    /**
     * @var \OnitsukaTigerKorea\Checkout\Helper\Data
     */
    protected $dataHelper;
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @param \OnitsukaTigerKorea\Checkout\Helper\Data $dataHelper
     * @param \Magento\Checkout\Model\Cart $cart
     * @param LayoutInterface $layout
     * @param $blockId
     */
    public function __construct(
        \OnitsukaTigerKorea\Checkout\Helper\Data $dataHelper,
        \Magento\Checkout\Model\Cart             $cart,
        LayoutInterface                          $layout,
        $blockId
    ) {
        $this->dataHelper = $dataHelper;
        $this->cart = $cart;
        $this->_layout = $layout;
        $this->cmsBlock = $this->constructBlock($blockId);
    }

    /**
     * @param $blockId
     * @return string
     */
    public function constructBlock($blockId)
    {
        $block = "";
        if ($this->dataHelper->isGiftPackagingEnabled()) {
            $block = $this->_layout->createBlock('Magento\Cms\Block\Block')
                ->setBlockId($blockId)
                ->toHtml();

        }
        return $block;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'gift_packaging_block_mobile' => $this->cmsBlock,
            'is_checkbox_show_mobile' => $this->dataHelper->isGiftPackagingEnabled() ? $this->checkIsProductContainPrefix() : 'false',
        ];
    }

    /**
     * @return string
     */
    public function checkIsProductContainPrefix()
    {
        $items = $this->cart->getItems();
        $skus = [];
        if ($items) {
            foreach ($items as $item) {
                $skus[] = $item->getSku();

            }
        }
        return $this->validateConditions($skus);
    }

    /**
     * @param $skus
     * @return bool
     */
    public function validateConditions($skus)
    {
        $startsWith3 = array_filter($skus, function ($sku) {
            return str_starts_with(trim($sku), trim($this->dataHelper->skuPrefix()));
        });

        $countTotal = count($skus);
        $countStartWith3 = count($startsWith3);

        if ($countStartWith3 === $countTotal) {
            return false; //ALL_START_WITH_PREFIX
        } elseif ($countStartWith3 === 0) {
            return true; //NONE_START_WITH_PREFIX
        } else {
            return true; //MIXED
        }
    }
}
