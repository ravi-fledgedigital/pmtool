<?php
namespace OnitsukaTigerIndo\GoogleTagManager\Block;

use OnitsukaTigerIndo\GoogleTagManager\Helper\Data as HelperData;

/**
 * Class \WeltPixel\GA4\Block\Checkout
 */
class Checkout extends \WeltPixel\GA4\Block\Checkout
{
    private HelperData $helperData;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context           $context,
        \WeltPixel\GA4\Helper\Data                                 $helper,
        \WeltPixel\GA4\Model\Storage                               $storage,
        \WeltPixel\GA4\Model\ServerSideStorage                     $serverSideStorage,
        \WeltPixel\GA4\Model\Dimension                             $dimensionModel,
        \WeltPixel\GA4\Model\CookieManager                         $cookieManager,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \WeltPixel\GA4\Model\ServerSide\JsonBuilder                $jsonBuilder,
        \Magento\Directory\Model\CountryFactory                    $countryFactory,
        \OnitsukaTigerIndo\GoogleTagManager\Helper\Data $helperData,
        \WeltPixel\GA4\Model\OrderTotalCalculator                  $orderTotalCalculator,
        array                                                      $data = []
    ) {
        $this->helperData = $helperData;
        parent::__construct(
            $context,
            $helper,
            $storage,
            $serverSideStorage,
            $dimensionModel,
            $cookieManager,
            $orderCollectionFactory,
            $jsonBuilder,
            $countryFactory,
            $orderTotalCalculator,
            $data
        );
    }

    /**
     * Returns the product details for the purchase gtm event
     * @return array
     */
    public function getProducts()
    {

        if (!$this->helperData->validStoreCode()) {
            return parent::getProducts();
        }
        $quote = $this->getQuote();
        $products = [];
        $displayOption = $this->helper->getParentOrChildIdUsage();

        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $productIdModel = $product;
            $productIdModelDimensions = $product;
            if ($displayOption == \WeltPixel\GA4\Model\Config\Source\ParentVsChild::CHILD) {
                if ($item->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                    $children = $item->getChildren();
                    foreach ($children as $child) {
                        $productIdModel = $child->getProduct();
                    }
                }
            } else {
                $children = $item->getChildren();
                foreach ($children as $child) {
                    $productIdModelDimensions = $child->getProduct();
                }
            }

            $productDetail = [];
            $productDetail['name'] = html_entity_decode($item->getName());
            $productDetail['id'] = $this->helper->getGtmProductId($productIdModel);
            $productDetail['price'] = number_format($item->getPriceInclTax(), 2, '.', '');
            if ($this->helper->isBrandEnabled()) {
                $productDetail['brand'] = $this->helper->getGtmBrand($product);
            }
            if ($this->helper->isVariantEnabled()) {
                $variant = $this->helper->checkVariantForProduct($product);
                if ($variant) {
                    $productDetail['variant'] = $variant;
                }
            }
            $categoryName =  $this->helper->getGtmCategoryFromCategoryIds($product->getCategoryIds());
            $productDetail['category'] = $categoryName;
            $productDetail['list'] = $categoryName;
            $productDetail['quantity'] = (double)$item->getQty();

            /**  Set the custom dimensions */

            $customDimensions = $this->getProductDimensions($productIdModelDimensions);

            foreach ($customDimensions as $name => $value) :
                $productDetail[$name] = $value;
            endforeach;

            $products[] = $productDetail;
        }

        return $products;
    }
}
