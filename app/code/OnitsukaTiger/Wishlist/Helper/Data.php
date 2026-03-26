<?php

namespace OnitsukaTiger\Wishlist\Helper;

use Magento\Framework\App\ActionInterface;

/**
 * Class Data
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Wishlist\Helper\Data
     */
    protected $_wishlistHelper;

    /**
     * @var \Magento\Framework\Data\Helper\PostHelper
     */
    protected $postDataHelper;

    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Wishlist\Model\ItemFactory
     */
    protected $itemFactory;

    /**
     * @var \Magento\Wishlist\Model\Item\OptionFactory
     */
    protected $optionFactory;

    /**
     * @var \OnitsukaTiger\PreOrders\Helper\PreOrder
     */
    protected $preOrderHelper;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    public function __construct(
        \Magento\Wishlist\Helper\Data $wishlistHelper,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Framework\View\Element\Context $contextBlock,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Wishlist\Model\ItemFactory $itemFactory,
        \Magento\Wishlist\Model\Item\OptionFactory $optionFactory,
        \OnitsukaTiger\PreOrders\Helper\PreOrder $preOrderHelper,
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->_wishlistHelper = $wishlistHelper;
        $this->postDataHelper = $postDataHelper;
        $this->_storeManager = $storeManager;
        $this->_urlBuilder = $contextBlock->getUrlBuilder();
        $this->productRepository = $productRepository;
        $this->itemFactory = $itemFactory;
        $this->optionFactory = $optionFactory;
        $this->preOrderHelper = $preOrderHelper;
        $this->cart = $cart;
        parent::__construct($context);
    }

    /**
     * Get add all to cart params for POST request
     *
     * @return string
     */
    public function getAddAllToCartParams()
    {
        return $this->postDataHelper->getPostData(
            $this->getUrl('wishlist/index/alltocart'),
            ['wishlist_id' => $this->getWishlistInstance()->getId()]
        );
    }

    /**
     * Get add selected to cart params for POST request
     *
     * @return string
     */
    public function getAddSelectedToCartParams()
    {
        return $this->postDataHelper->getPostData(
            $this->getUrl('wishlist/index/selectedcart'),
            ['wishlist_id' => $this->getWishlistInstance()->getId()]
        );
    }

    /**
     * Retrieve URL for adding item to shopping cart
     *
     * @param string|\Magento\Catalog\Model\Product|\Magento\Wishlist\Model\Item $item
     * @param bool $addReferer
     * @return string
     */
    public function getItemAddToCartParams($item, $addReferer = false)
    {
        $params = $this->_getCartUrlParameters($item);
        $params[ActionInterface::PARAM_NAME_URL_ENCODED] = '';

        return $this->postDataHelper->getPostData(
            $this->_getUrlStore($item)->getUrl('wishlist/index/tocart'),
            $params
        );
    }

    /**
     * Retrieve Item Store for URL
     *
     * @param \Magento\Catalog\Model\Product|\Magento\Wishlist\Model\Item $item
     * @return \Magento\Store\Model\Store
     */
    protected function _getUrlStore($item)
    {
        $storeId = null;
        $product = null;
        if ($item instanceof \Magento\Wishlist\Model\Item) {
            $product = $item->getProduct();
        } elseif ($item instanceof \Magento\Catalog\Model\Product) {
            $product = $item;
        }
        if ($product) {
            if ($product->isVisibleInSiteVisibility()) {
                $storeId = $product->getStoreId();
            } else {
                if ($product->hasUrlDataObject()) {
                    $storeId = $product->getUrlDataObject()->getStoreId();
                }
            }
        }
        return $this->_storeManager->getStore($storeId);
    }
    /**
     * Get cart URL parameters
     *
     * @param string|\Magento\Catalog\Model\Product|\Magento\Wishlist\Model\Item $item
     * @return array
     */
    protected function _getCartUrlParameters($item)
    {
        $params = [
            'item' => is_string($item) ? $item : $item->getWishlistItemId(),
        ];
        if ($item instanceof \Magento\Wishlist\Model\Item) {
            $params['qty'] = $item->getQty();
        }
        return $params;
    }

    /**
     * Retrieve wishlist instance
     *
     * @return \Magento\Wishlist\Model\Wishlist
     */
    public function getWishlistInstance()
    {
        return $this->_getWishlist();
    }

    /**
     * Retrieve Wishlist model
     *
     * @return \Magento\Wishlist\Model\Wishlist
     */
    protected function _getWishlist()
    {
        return $this->_getHelper()->getWishlist();
    }
    /**
     * Retrieve Wishlist Data Helper
     *
     * @return \Magento\Wishlist\Helper\Data
     */
    protected function _getHelper()
    {
        return $this->_wishlistHelper;
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->_urlBuilder->getUrl($route, $params);
    }

    /**
     * get product data by sku
     *
     * @param   string $sku
     * @return  obj
     */
    public function getProductBySku($sku)
    {
        return $this->productRepository->get($sku);
    }

    /**
     * check selected wish list items is pre order enabled
     *
     * @param   obj $wishlistItems
     * @return  bool
     */
    public function getPreOrderSelectedItem($wishlistItems)
    {
        $isPreOrderWishListItem = false;

        foreach($wishlistItems as $wishlistItems){
            $itemId = $wishlistItems->getWishlistItemId();
            $item = $this->itemFactory->create()->load($itemId);

            /** @var \Magento\Wishlist\Model\ResourceModel\Item\Option\Collection $options */
            $options = $this->optionFactory->create()->getCollection()->addItemFilter([$itemId]);
            $item->setOptions($options->getOptionsByItem($itemId));
            $simpleProductsku = $item->getProduct()->getSku();

            $productId = $this->getProductBySku($simpleProductsku)->getId();

            $isPreOrderWishListItem = $this->preOrderHelper->isProductPreOrder($productId);
            if($isPreOrderWishListItem){
                break;
            }
        }

        return $isPreOrderWishListItem;
    }

    /**
     * @param $wishlistItems
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isProductForceOutOfStock($wishlistItems)
    {
        $flag = false;
        foreach ($wishlistItems as $wishlistItem) {
            $itemId = $wishlistItem->getWishlistItemId();
            $item = $this->itemFactory->create()->load($itemId);

            /** @var \Magento\Wishlist\Model\ResourceModel\Item\Option\Collection $options */
            $options = $this->optionFactory->create()->getCollection()->addItemFilter([$itemId]);
            $item->setOptions($options->getOptionsByItem($itemId));
            $simpleProductsku = $item->getProduct()->getSku();

            $product = $this->getProductBySku($simpleProductsku);
            if ($product->getForceOosToggle()) {
                $flag = true;
                break;
            }
        }
        return $flag;
    }

    public function isProductComingSoon($wishlistItems)
    {
        $flag = false;
        foreach ($wishlistItems as $wishlistItem) {
            $itemId = $wishlistItem->getWishlistItemId();
            $item = $this->itemFactory->create()->load($itemId);

            /** @var \Magento\Wishlist\Model\ResourceModel\Item\Option\Collection $options */
            $options = $this->optionFactory->create()->getCollection()->addItemFilter([$itemId]);
            $item->setOptions($options->getOptionsByItem($itemId));
            $isProductComingSoon = 0;
            $currentTime = date('Y-m-d H:i:s');
            $launchDate = $item->getProduct()->getLaunchDate();
            if (!empty($launchDate) && strtotime($launchDate) > strtotime($currentTime)) {
                $isProductComingSoon = 1;
            }
            if ($isProductComingSoon == 1) {
                $flag = true;
                break;
            }
        }
        return $flag;
    }

    /**
     * check cart items are pre order enabled
     *
     * @return  bool
     */
    public function getPreOrderCartItem()
    {
        $currentCartItems = $this->cart->getQuote()->getAllItems();
        $isPreOrderCartItem = false;

        foreach ($currentCartItems as $cartItems) {
            if($cartItems->getProductType() == 'simple'){
                $isPreOrderCartItem  = $this->preOrderHelper->isProductPreOrder($cartItems->getProductId());
                if($isPreOrderCartItem){
                    break;
                }
            }
        }

        return $isPreOrderCartItem;
    }
}
