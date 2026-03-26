<?php
namespace Vaimo\OTAdobeDataLayer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\Session;

class AddToCartProductAfter implements ObserverInterface
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
        \Magento\Framework\DataObject\Factory $objectFactory
    ) {
        $this->dataLayerHelper = $dataLayerHelper;
        $this->requestParams = $requestParams;
        $this->_session = $session;
        $this->_objectManager = $objectManager;
        $this->objectFactory = $objectFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if($this->dataLayerHelper->isEnabledAdobeLaunch()){
            $product = $observer->getData('product');
            $request = $observer->getData('request');
            $params = $request->getParams();

            $productId = $product->getId();
            if ( $product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                $canditatesRequest = $this->objectFactory->create($params);
                $cartCandidates = $product->getTypeInstance()->prepareForCartAdvanced($canditatesRequest, $product);

                foreach ($cartCandidates as $candidate) {
                    if ($candidate->getParentProductId())  {
                        $productId = $candidate->getId();
                    }
                }
            }

            $catName = '';
            if($product->getCategoryIds() && isset($product->getCategoryIds()[0])){
                $category = $this->dataLayerHelper->getCategoryLoadById($product->getCategoryIds()[0]);
                $catName = $category->getName();
            }
            $productRepo  = $this->dataLayerHelper->loadProductById($productId);

            $productData = [];
            $productData['sku'] = $product->getSku();
            $productData['name'] = html_entity_decode($product->getName() ?? '');
            $productData['productId'] = (int)$productId;
            $productData['category'] = $catName;
            $productData['brand'] = ($productRepo->getBrands()) ? $productRepo->getAttributeText('brands'): '';
            $productData['color'] = ($productRepo->getColor()) ? $productRepo->getAttributeText('color') : '';
            $productData['size'] = ($productRepo->getQaSize()) ? $productRepo->getAttributeText('qa_size'): '';
            $productData['quantity'] = (isset($params['qty'])) ? (int)$params['qty'] : 0;
            $productData['currencyCode'] = $this->dataLayerHelper->getCurrencyCode();
            $productData['priceTotal'] = floatval($product->getPrice());
            $productData['discountAmount'] =  floatval(($product->getSpecialPrice() ? $product->getPrice() - $product->getSpecialPrice(): 0));
            $productData['unitOfMeasureCode'] = 'ft';

            $this->dataLayerHelper->getAddToCartEvent($productData, $params, $product);
        }
    }
}
