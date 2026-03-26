<?php
namespace Vaimo\OTAdobeDataLayer\Block\Track;

/**
 * Class \Vaimo\OTAdobeDataLayer\Block\Track\Cart
 */
class Cart extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Vaimo\OTAdobeDataLayer\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Checkout\Model\Cart $cartSession
     * @param \Vaimo\OTAdobeDataLayer\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Model\Cart $cartSession,
        \Vaimo\OTAdobeDataLayer\Helper\Data $helper,
        array $data = []
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->cartSession = $cartSession;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * push cartview data in datalyer event
     * @return array
     */
    public function pushViewCartEvent()
    {
        $quote = $this->checkoutSession->getQuote()->getAllVisibleItems();

        $returnData = $optionsArr = [];

        foreach ($quote as $item) {
            $catName = '';
            $product =  $this->helper->getProductBySku($item->getSku());
            $parentProducts =  $this->helper->loadProductById($item->getProductId());

            if($parentProducts->getCategoryIds() && isset($parentProducts->getCategoryIds()[0])){
                $category = $this->helper->getCategoryLoadById($parentProducts->getCategoryIds()[0]);
                $catName = $category->getName();
            }
            $returnData[] = [
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'productId' => (int)$product->getId(),
                'category' => $catName,
                'brand' => ($parentProducts->getBrands()) ? $parentProducts->getAttributeText('brands'): '',
                'color' => ($product->getColor()) ? $product->getAttributeText('color'): '',
                'size' => ($product->getQaSize()) ? $product->getAttributeText('qa_size'): '',
                'quantity' => (int)$item->getQty(),
                'currencyCode' => $this->helper->getCurrencyCode(),
                'priceTotal' => floatval($item->getPrice()),
                'discountAmount' => floatval(($item->getSpecialPrice() ? $item->getPrice()-$item->getSpecialPrice(): 0)),
                'unitOfMeasureCode' => 'ft'
            ];
        }
        return json_encode($returnData);
    }

}
