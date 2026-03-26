<?php

namespace OnitsukaTiger\Catalog\Controller\Product;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Locale\Format;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory as OptionCollectionFactory;

class View extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $productRepository;
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $_attribute;
    /**
     * @var \Magento\Catalog\Block\Product\ListProduct
     */
    protected $listProduct;
    /**
     * @var Format
     */
    private $localeFormat;
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    protected $optionCollectionFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    protected $getSalableQty;

    /**
     *
     * @param Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $attribute
     * @param \Magento\Catalog\Block\Product\ListProduct $listProduct
     * @param Format $localeFormat
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteId
     * @param \OnitsukaTiger\Catalog\Helper\ConfigurablePrice $configurablePriceHelper
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     */
    public function __construct(
        Context                                                                              $context,
        \Magento\Framework\Controller\Result\JsonFactory                                     $resultJsonFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface                                      $productRepository,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute                                    $attribute,
        \Magento\Catalog\Block\Product\ListProduct                                           $listProduct,
        Format                                                                               $localeFormat,
        \Magento\Framework\Pricing\Helper\Data                                               $priceHelper,
        \Magento\Framework\Registry                                                          $registry,
        \Magento\Store\Model\StoreManagerInterface                                           $storeManagerInterface,
        protected GetStockItemConfigurationInterface                                         $getStockItemConfiguration,
        protected StockByWebsiteIdResolverInterface                                          $stockByWebsiteId,
        protected \OnitsukaTiger\Catalog\Helper\ConfigurablePrice                            $configurablePriceHelper,
        protected \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType,
        protected \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory             $productCollectionFactory,
        protected \OnitsukaTiger\PreOrders\Helper\PreOrder                                   $preOrderHelper,
        protected \Magento\Eav\Model\Config                                                  $eavConfig,
        OptionCollectionFactory                                                              $optionCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\InventorySalesApi\Model\GetSalableQtyInterface $getSalableQty
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productRepository = $productRepository;
        $this->_attribute = $attribute;
        $this->listProduct = $listProduct;
        $this->localeFormat = $localeFormat;
        $this->priceHelper = $priceHelper;
        $this->_coreRegistry = $registry;
        $this->storeManager = $storeManagerInterface;
        $this->optionCollectionFactory = $optionCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->getSalableQty = $getSalableQty;
    }

    /**
     * Function execute
     *
     * @return string
     * phpcs:disable
     */
    public function execute()
    {
        $postData = $this->getRequest()->getPost();
        $resultJson = $this->resultJsonFactory->create();
        $sizeAttrId = $this->_attribute->getIdByCode('catalog_product', 'qa_size');
        $data = [];
        $newurl = '';
        $urlFlag = false;
        $query_params = [];

        $url = $this->getRequest()->getParam('productPageUrl') ?? '';
        if (parse_url($url, PHP_URL_QUERY)) {
            $query_str = parse_url($url, PHP_URL_QUERY);
            parse_str($query_str, $query_params);
        }

        if (count($query_params)) {
            $urlFlag = true;
            foreach ($query_params as $key => $value) {
                if ($key != 'url') {
                    if ($value != '') {
                        $newurl .= '&' . $key . '=' . $value;
                    }
                }
            }
        }

        if (isset($postData['id']) && !empty($postData['id'])) {
            $product = $this->productRepository->getById($postData['id']);
            $this->_coreRegistry->register('product', $product);
            $getAddtoCartUrl = $this->getAddtoCartUrl($product);

            if ($urlFlag) {
                $search = '/' . preg_quote('&', '/') . '/';
                $productUrl = $product->getProductUrl() . preg_replace($search, '?', $newurl, 1);
            } else {
                $productUrl = $product->getProductUrl();
            }

            if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                $_children = $this->getSimpleProducts($product);
                $isProductComingSoon = 0;
                $currentTime = date('Y-m-d H:i:s');
                $launchDate = $product->getLaunchDate();
                if (!empty($launchDate) && strtotime($launchDate) > strtotime($currentTime)) {
                    $isProductComingSoon = 1;
                }
                if (!empty($_children)) {
                    $sizeHtml = "";
                    $i = 0;
                    $minPrice = [];
                    $childProductsArray = [];
                    foreach ($_children as $childProduct) {

                        // get product data by product id
                        $child = $this->productRepository->getById($childProduct['entity_id']);
                        $childProductsArray[] = $child;
                        if ($postData['coloroptionid'] != $child->getColorCode()) {
                            continue;
                        }
                        if ($child->getStatus() == 2) {
                            continue;
                        }
                        $sizeLabel = "";
                        if ($child->getResource()->getAttribute('qa_size')) {
                            $sizeLabel = $child->getResource()->getAttribute('qa_size')->getFrontend()->getValue($child);
                        }

                        $displaySizeLabel = $sizeLabel;

                        if ($child->getSizeForDisplay() && !empty($child->getSizeForDisplay())) {
                            $displaySizeLabel = $child->getSizeForDisplay();
                        }

                        $getsize = $child->getQaSize();
                        $getColor = $child->getColorCode();

                        $productId = $child->getId();
                        $customDisableClass = $customClass = $restockClass = "";
                        $customDisabled = 0;

                        $isSourceAssigned = true;

                        $stockId = (int)$this->stockByWebsiteId->execute((int)$this->storeManager->getStore()->getWebsiteId())->getStockId();

                        try {
                            /** @var StockItemConfigurationInterface $stockItemConfiguration */
                            $stockItemConfiguration = $this->getStockItemConfiguration->execute($child->getSku(), $stockId);
                        } catch (SkuIsNotAssignedToStockException $exception) {
                            /*$this->logger->critical($exception);*/
                            $isSourceAssigned = false;
                        }
                        $forceOutOfStockProduct = $child->getForceOosToggle() ?? 0;
                        $isPreOrderEnabled = $this->preOrderHelper->isProductPreOrder($child->getId());
                        if ($isPreOrderEnabled) {
                            $customDisableClass = '';
                        } elseif (!$child->getIsSalable() && $child->getRestockNotificationFlag() == 2) {
                            $restockClass = 'out-of-stock';
                        } elseif (!$isSourceAssigned || !$child->getIsSalable() && $customDisabled == 0) {
                            $customDisableClass = ' size-swatch-disabled';
                        }



                        if ($forceOutOfStockProduct && $child->getRestockNotificationFlag() == 2) {
                            $customDisableClass = ' out-of-stock size-swatch-force-out-of-stock';
                        } elseif ($forceOutOfStockProduct) {
                            $customDisableClass = ' size-swatch-force-out-of-stock';
                        }

                        $formattedPrice = "";
                        $price = "";

                        if ($child->getPriceInfo()->getPrice('final_price')) {
                            $price = $child->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
                        }

                        if (!empty($price)) {
                            array_push($minPrice, $price);
                            $formattedPrice = $this->priceHelper->currency($price, true, false);
                        }

                        $preOrderClass = $preOrderNote = '';
                        $buttonLabel = 'data-button-label="' . __('Add to Cart') . '"';
                        if ($isPreOrderEnabled) {
                            $buttonLabel = 'data-button-label="' . __('Pre-Order') . '"';
                            $preOrderNote = 'data-pre-order-note="' . $this->preOrderHelper->getPreOrderStatusLabelByProductId($child->getId()) . '"';
                            $preOrderClass = 'pre-order-item';
                            $storeId = $this->storeManager->getStore()->getId();
                            $salableQty = $this->getSalableQty->execute($child->getSku(),$storeId);
                            if(!$salableQty){
                                $preOrderClass = 'pre-order-item-disabled';
                            }
                        }

                        $comingSoonClass = "";
                        if($isProductComingSoon){
                            $comingSoonClass = "coming-soon-child-product";
                        }

                        $sizeHtml .= "<div class='swatch-option size-swatch-option text$customDisableClass$customClass $restockClass $preOrderClass $comingSoonClass' id='option-label-size-$sizeAttrId-item-$getsize' index='$i' aria-checked='false' aria-describedby='option-label-size-$sizeAttrId' tabindex='0' data-option-type='0' data-option-id='$getsize' data-product-id='$productId' data-option-label='$displaySizeLabel' aria-label='$sizeLabel' role='option' data-thumb-width='110' data-thumb-height='90' data-option-tooltip-value='$displaySizeLabel' data-final-child-product-price='$formattedPrice' data-parent-product-id='" . $product->getId() . "' $buttonLabel $preOrderNote>$displaySizeLabel</div>";
                        $i++;

                        $data["price"]["$getColor"]["$getsize"] = $formattedPrice;
                    }
                    $data['size_option'] = $sizeHtml;
                }
                $productStatus = $this->checkProductStatus($childProductsArray);
                $notifyMe = $productStatus['notifyMe'];
                $outOfStock = $productStatus['outOfStock'];

                $data['all_product_restock'] = $notifyMe;
                $data['all_product_oos'] = $outOfStock;
                $data['addtocart_url'] = $getAddtoCartUrl;
                $data['product_name'] = $product->getName();
                $data['gender'] = $product->getAttributeText('gender') ?? '';
                $data['product_description'] = !empty($product->getDescription()) ? nl2br(html_entity_decode($product->getDescription())) : '';

                $storeId = $this->getStoreId();
                $styleCode = $product->getStyleCode();
                $productColor = $product->getAttributeText('color_code');
                $productManufacturer = $product->getResource()->getAttribute('country_of_manufacture')->setStoreId($storeId)->getFrontend()->getValue($product); //$product->getAttributeText('country_of_manufacture');
                $productPrice = $product->getPrice();
                $productSalesPrice = $product->getSalesPrice();

                $data['style_code'] = $styleCode;
                $data['product_color'] = $productColor;
                $data['country_of_manufacture'] = $productManufacturer;
                $data['price_attributes'] = $productPrice;
                $data['main_price'] = !empty($minPrice) ? $this->priceHelper->currency(min($minPrice), true, false) : '';
                $data['sales_price'] = $productSalesPrice;
                $data['coming_soon'] = $isProductComingSoon;
                $data['coming_soon_label'] = $this->getConfig('coming_soon/general/coming_soon_label');
                if ($product->isAvailable()) {
                    $data['stock_status'] = __("In stock");
                } else {
                    $data['stock_status'] = __("Out of stock");
                }
                $data['product_sku'] = $product->getSku();
                $data['product_url'] = $productUrl;
                /*$storeId = $this->storeManager->getStore()->getId();

                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $customerSession = $objectManager->create(\Magento\Customer\Model\Session::class);
                $isAddToCartEnabled = false;

                if (in_array($storeId, [8, 10])) {
                    if ($customerSession->isLoggedIn() && $customerSession->getCustomerGroupId() == 6) {
                        $isAddToCartEnabled = true;
                    }
                } else {
                    $isAddToCartEnabled = true;
                }
                $data['enable_add_to_cart'] = $isAddToCartEnabled;*/
            }
        }
        $jsonData = "";
        if (!empty($data)) {
            $jsonData = json_encode($data);
        }

        $resultJson->setData([
            "success" => true,
            'jsonResponse' => $jsonData
        ]);
        return $resultJson;
    }
    /** phpcs:enable */

    /**
     * Function getAddtoCartUrl
     *
     * @param object $product
     *
     * @return string
     */
    public function getAddtoCartUrl($product)
    {
        return $this->listProduct->getAddToCartUrl($product);
    }

    /**
     * Get store id
     *
     * @return string
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Get simple products of a configurable product, including out-of-stock products
     *
     * @param $product
     * @return array|\Magento\Framework\DataObject[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSimpleProducts($product)
    {
        $simpleProductIds = $this->configurableType->getChildrenIds($product->getId());
        $productGroup = $product->getAttributeText('product_group');
        $productGroupId = $product->getProductGroup();
        if (isset($simpleProductIds[0])) {
            $productCollection = $this->productCollectionFactory->create()
                ->addStoreFilter($this->storeManager->getStore())
                ->addAttributeToSelect(['restock_notification_flag', 'size_for_display', 'force_oos_toggle'])
                ->addAttributeToSort('qa_size', 'ASC')
                ->addAttributeToFilter(
                    'status',
                    \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
                );
            $productCollection->addIdFilter($simpleProductIds[0]);
        }

        if ($productCollection) {
            $products = $productCollection->getItems();

            $sizeSortOrders = $this->getSizeOptionSortOrders(); // NEW: dynamic sort order from DB

            usort($products, function ($a, $b) use ($sizeSortOrders) {
                $aSize = $a->getData('qa_size'); // this is an int (option_id)
                $bSize = $b->getData('qa_size');
                $sortA = $sizeSortOrders[$aSize] ?? PHP_INT_MAX;
                $sortB = $sizeSortOrders[$bSize] ?? PHP_INT_MAX;
                return $sortA - $sortB;
            });

            return $products;
        }
        return [];
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSizeOptionSortOrders(): array
    {
        $sizeSortOrders = [];

        $attribute = $this->eavConfig->getAttribute('catalog_product', 'qa_size');
        $attributeId = $attribute->getId();

        $optionCollection = $this->optionCollectionFactory->create()
            ->setAttributeFilter($attributeId)
            ->setPositionOrder('asc', true);

        foreach ($optionCollection as $option) {
            $valueId = $option->getId();
            $sortOrder = (int)$option->getSortOrder();
            if ($valueId) {
                $sizeSortOrders[$valueId] = $sortOrder;
            }
        }

        return $sizeSortOrders;
    }
    /**
     * @param $_children
     * @return int|int[]
     */
    public function checkProductStatus($_children)
    {
        $isProductOutOfStock = 0;
        $isProductNotifyMe = 0;
        if ($_children) {
            $childOutOfStock = 0;
            $childNotify = 0;
            $noOfChildProducts = 0;
            foreach ($_children as $childProduct) {
                if (!$childProduct->getIsSalable()) {
                    $childOutOfStock++;
                }
                if (!$childProduct->getIsSalable() && $childProduct->getRestockNotificationFlag() == 2) {
                    $childNotify++;
                }
                $noOfChildProducts++;
            }
            if ($childOutOfStock > 0 && $noOfChildProducts == $childOutOfStock) {
                $isProductOutOfStock = 1;
            }
            if ($childNotify > 0 && $noOfChildProducts == $childNotify) {
                $isProductNotifyMe = 1;
            }
        }
        return [
            'outOfStock' => $isProductOutOfStock,
            'notifyMe' => $isProductNotifyMe
        ];
    }

    /**
     * @param $config_path
     * @return mixed
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
