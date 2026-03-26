<?php

namespace OnitsukaTigerKorea\GA4\Block;

use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use OnitsukaTigerKorea\GA4\Helper\Data as HelperData;
use WeltPixel\GA4\Helper\Data;
use WeltPixel\GA4\Model\CookieManager;
use WeltPixel\GA4\Model\Dimension;
use WeltPixel\GA4\Model\OrderTotalCalculator;
use WeltPixel\GA4\Model\ServerSide\JsonBuilder;
use WeltPixel\GA4\Model\ServerSideStorage;
use WeltPixel\GA4\Model\Storage;

class Cart extends \WeltPixel\GA4\Block\Cart
{
    /**
     * @var HelperData
     */
    protected HelperData $helperData;

    /**
     * @param Context $context
     * @param Data $helper
     * @param Storage $storage
     * @param ServerSideStorage $serverSideStorage
     * @param Dimension $dimensionModel
     * @param CookieManager $cookieManager
     * @param CollectionFactory $orderCollectionFactory
     * @param JsonBuilder $jsonBuilder
     * @param CountryFactory $countryFactory
     * @param OrderTotalCalculator $orderTotalCalculator
     * @param HelperData $helperData
     * @param array $data
     */
    public function __construct(
        Context              $context,
        Data                 $helper,
        Storage              $storage,
        ServerSideStorage    $serverSideStorage,
        Dimension            $dimensionModel,
        CookieManager        $cookieManager,
        CollectionFactory    $orderCollectionFactory,
        JsonBuilder          $jsonBuilder,
        CountryFactory       $countryFactory,
        OrderTotalCalculator $orderTotalCalculator,
        HelperData           $helperData,
        array                $data = []
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
     * @return int
     */
    public function getCartTotalQty(): int
    {
        return (int)$this->getQuote()->getItemsQty();
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProducts(): array
    {
        $quote = $this->getQuote();
        $products = [];
        $displayOption = $this->helper->getParentOrChildIdUsage();

        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $productIdModel = $product;

            if ($displayOption == \WeltPixel\GA4\Model\Config\Source\ParentVsChild::CHILD
                || $this->helperData->isKoreaWebsite()
            ) {
                if ($item->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                    $children = $item->getChildren();
                    foreach ($children as $child) {
                        $productIdModel = $child->getProduct();
                    }
                }
            }
            $originalPrice = $item->getProduct()->getPriceInfo()->getPrice('regular_price')->getValue();
            $productDetail = [];
            $productDetail['currency'] = $this->getCurrencyCode();
            $productDetail['item_name'] = html_entity_decode($item->getName());
            $productDetail['item_id'] = $this->helper->getGtmProductId($productIdModel);
            $productDetail['price'] = number_format($item->getPriceInclTax(), 2, '.', '');
            $productDetail['originalPrice'] = number_format($originalPrice, 2, '.', '');
            if ($this->helper->isBrandEnabled()) {
                $productDetail['item_brand'] = $this->helper->getGtmBrand($product);
            }
            if ($this->helper->isVariantEnabled()) {
                $variant = $this->helper->checkVariantForProduct($product);
                if ($variant) {
                    $productDetail['item_variant'] = $variant;
                }
            }
            $productCategoryIds = $product->getCategoryIds();
            $categoryName = $this->helper->getGtmCategoryFromCategoryIds($productCategoryIds);
            $ga4Categories = $this->helper->getGA4CategoriesFromCategoryIds($productCategoryIds);
            $productDetail = array_merge($productDetail, $ga4Categories);
            $productDetail['item_list_name'] = $categoryName;
            $productDetail['item_list_id'] = count($productCategoryIds) ? $productCategoryIds[0] : '';
            $productDetail['quantity'] = (double)$item->getQty();

            /**  Set the custom dimensions */
            $customDimensions = $this->getProductDimensions($product);
            foreach ($customDimensions as $name => $value) :
                $productDetail[$name] = $value;
            endforeach;

            $products[] = $productDetail;
        }

        return $products;
    }

}
