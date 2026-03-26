<?php
namespace Vaimo\OTAdobeDataLayer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\Session;

class WishListProductAddAfter implements ObserverInterface
{
    /**
     * @var \Vaimo\OTAdobeDataLayer\Helper\Data
     */
    protected $dataLayerHelper;

    protected $requestParams;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $objectFactory;

    protected $type;

    protected $productFactory;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * add to cart constructor.
     * @param \Vaimo\OTAdobeDataLayer\Helper\Data $dataLayerHelper
     */
    public function __construct(
        \Vaimo\OTAdobeDataLayer\Helper\Data $dataLayerHelper,
        RequestInterface $requestParams,
        Session $session,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\DataObject\Factory $objectFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $type
    ) {
        $this->dataLayerHelper = $dataLayerHelper;
        $this->requestParams = $requestParams;
        $this->_session = $session;
        $this->_objectManager = $objectManager;
        $this->objectFactory = $objectFactory;
        $this->productFactory = $productFactory;
        $this->type = $type;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if($this->dataLayerHelper->isEnabledAdobeLaunch()){
            $items = $observer->getItems();
            $wishListData = [];
            $priceTotal = 0;
            foreach ($items as $key => $item) {
                $wishlistItem = $item->getBuyRequest();
                $supper = $wishlistItem->getSuperAttribute();
                $product = $this->productFactory->create()->load($wishlistItem->getProduct());
                $product->getSku();
                if ($product->getTypeId()=="configurable") {
                    if (!empty($supper)) {
                        $catName = '';
                        if($product->getCategoryIds() && isset($product->getCategoryIds()[0])){
                            $category = $this->dataLayerHelper->getCategoryLoadById($product->getCategoryIds()[0]);
                            $catName = $category->getName();
                        }

                        $wishListData[] = [
                            'sku' => $product->getSku(),
                            'name' => $product->getName(),
                            'productId' => (int)$product->getId(),
                            'category' => ($catName ? $catName : ''),
                            'brand' => ($product->getBrands()) ? $product->getAttributeText('brands'): '',
                            'color' => ($product->getColor()) ? $product->getAttributeText('color'): '',
                            'size' => ($product->getSize()) ? $product->getAttributeText('size'): '',
                            'quantity' => (int)$product->getQty(),
                            'currencyCode' => $this->dataLayerHelper->getCurrencyCode(),
                            'priceTotal' => floatval($product->getPrice()),
                            'discountAmount' => floatval(($product->getSpecialPrice() ? $product->getPrice()-$product->getSpecialPrice(): 0)),
                            'unitOfMeasureCode' => 'ft',
                        ];
                    }
                }
            }

            $this->dataLayerHelper->setAddToWishListEvent($wishListData);
        }
    }
}
