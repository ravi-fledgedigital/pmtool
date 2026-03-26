<?php

namespace Vaimo\OTAdobeDataLayer\Helper;

use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session;
use Vaimo\OTAdobeDataLayer\Api\ConfigInterface;
use Vaimo\AepEventStreaming\Service\CustomerId;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Class Data
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Get country path
     */
    const COUNTRY_CODE_PATH = 'general/country/default';

    private const CUSTOMER_ID = 'customerId';
    private const USER_HASHED_ID = 'userHashedId';
    private const USER_LOGGED_IN = 'userLoggedIn';
    private const LOGGED_IN_SITE = 'loggedInSite';
    private const LOGGED_IN_SITE_LANGUAGE = 'loggedInSiteLanguage';
    private const LOGGED_IN_REGION = 'loggedInRegion';
    private const LOGGED_IN_COUNTRY = 'loggedInCountry';
    private const IS_ENABLE_ADOBELAUNCH = 'adobe_launch/general/enabled';
    private const SITE_LANGAUGE = 'adobe_launch/general/site_language';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private Session $customerSession;

    /**
     * @var \Vaimo\OTAdobeDataLayer\Api\ConfigInterface;
     */
    private ConfigInterface $dataLayerConfig;

    private CustomerId $customerIdService;

    private EncryptorInterface $encryptor;

    private $vaimoConfig;

    private $storeManager;

    protected $productRepository;

    protected $wishlist;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    private $httpContext;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $categoryFactory;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $stockState;

    /**
     * @var \OnitsukaTiger\OrderStatusTracking\Helper\Data
     */
    protected $helperTrack;

    protected $request;

    protected $configurable;

    protected $jsonSerializer;

    /**
     * Data constructor.
     * @param ConfigProvider $configProvider
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \OnitsukaTiger\OrderStatusTracking\Helper\Data $helperTrack
     * @param Registry $registry
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Vaimo\OTAdobeDataLayer\Model\Config $vaimoConfig
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockStateInterface $stockState
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \OnitsukaTiger\OrderStatusTracking\Helper\Data $helperTrack,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
        Registry $registry,
        \Vaimo\OTAdobeDataLayer\Model\Config $vaimoConfig,
        Session $customerSession,
        ConfigInterface $dataLayerConfig,
        CustomerId $customerIdService,
        EncryptorInterface $encryptor,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        ProductRepositoryInterface $productRepository,
        \Magento\Wishlist\Model\Wishlist $wishlist,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\App\State $appState,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState
    ) {
        parent::__construct($context);
        $this->helperTrack = $helperTrack;
        $this->scopeConfig = $scopeConfig;
        $this->registry = $registry;
        $this->request = $request;
        $this->configurable = $configurable;
        $this->vaimoConfig = $vaimoConfig;
        $this->customerSession = $customerSession;
        $this->dataLayerConfig = $dataLayerConfig;
        $this->customerIdService = $customerIdService;
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
        $this->jsonSerializer = $jsonSerializer;
        $this->productRepository = $productRepository;
        $this->wishlist = $wishlist;
        $this->httpContext = $httpContext;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->cookieManager = $cookieManager;
        $this->appState = $appState;
        $this->priceCurrency = $priceCurrency;
        $this->categoryFactory = $categoryFactory;
        $this->stockRegistry = $stockRegistry;
        $this->stockState = $stockState;
    }

    /**
     * @return array
     */
    public function getCustomerDataLayer() {
        return [
            "pageInfo" => [
                "country" => $this->getDefaultCountry(),
                "region" => $this->vaimoConfig->getLoggedInRegion(),
                "language" => $this->getStoreLanguage()
            ],
            'userInfo' => [
                self::USER_HASHED_ID => ($this->getUserHashedId() ? $this->getUserHashedId() : ''),
                self::USER_LOGGED_IN => $this->getCustomerIsLoggedIn(),
                self::LOGGED_IN_SITE => $this->dataLayerConfig->getLoggedInSite(),
                self::LOGGED_IN_SITE_LANGUAGE => $this->getStoreLanguage(),
                self::LOGGED_IN_REGION => $this->dataLayerConfig->getLoggedInRegion(),
                self::LOGGED_IN_COUNTRY => \strtoupper($this->getDefaultCountry())
            ]
        ];
    }

    /**
     * @return array
     */
    public function getUserInfo() {
        return [
            self::USER_HASHED_ID => ($this->getUserHashedId() ? $this->getUserHashedId() : ''),
            self::USER_LOGGED_IN => $this->getCustomerIsLoggedIn(),
            self::LOGGED_IN_SITE => $this->dataLayerConfig->getLoggedInSite(),
            self::LOGGED_IN_SITE_LANGUAGE => $this->getStoreLanguage(),
            self::LOGGED_IN_REGION => $this->dataLayerConfig->getLoggedInRegion(),
            self::LOGGED_IN_COUNTRY => \strtoupper($this->getDefaultCountry())
        ];
    }

    /**
     * @return array
     */
    public function getPageInfo() {
        return json_encode([
            "pageInfo" => [
                "country" => $this->getDefaultCountry(),
                "region" => $this->vaimoConfig->getLoggedInRegion(),
                "language" => $this->getStoreLanguage()
            ]
        ]);
    }

    /**
     * @return array
     */
    public function getPageInfoForProdView() {
        return json_encode([
            "pageInfo" => [
                "country" => $this->getDefaultCountry(),
                "region" => $this->vaimoConfig->getLoggedInRegion(),
                "language" => $this->getStoreLanguage(),
                "siteName" => $this->vaimoConfig->getLoggedInSite(),
                "siteEnvironment" => 'prod'
            ]
        ]);
    }

    private function getDefaultCountry()
    {
        return $this->scopeConfig->getValue(
            self::COUNTRY_CODE_PATH,
            ScopeInterface::SCOPE_WEBSITES
        );
    }

    private function getStoreCode()
    {
        return $this->storeManager->getStore()->getCode();
    }

    private function getCustomerId(): ?string
    {
        $customerId  = '';

        if ($this->getCustomerIsLoggedIn()) {
            $customerId = (int) $this->customerSession->getCustomerId();
        }

        return $customerId ? $this->customerIdService->getById($customerId) : '';
    }

    private function getUserHashedId(): ?string
    {
        $customerId = $this->getCustomerId();

        return $customerId ? $this->encryptor->hash($customerId) : '';
    }

    public function getStoreLocale(): ?string
    {
        return $this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE);
    }

    public function loadProductById($productId)
    {
        return $this->productRepository->getById($productId);
    }

    public function getCustId()
    {
        $customerId = '';
        if ($this->getCustomerIsLoggedIn()) {
            $customerId = (int) $this->customerSession->getCustomerId();
        }

        return $customerId;
    }
    public function getCustomerIsLoggedIn()
    {
        $isLoggedIn = $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);

        return $isLoggedIn;
    }

    /**
     * get product details load by by id
     * @return array
     */
    public function getProductListItem($productId)
    {
        $product = $this->loadProductById($productId);

        $catName = '';
        if($product->getCategoryIds() && isset($product->getCategoryIds()[0])){
            $category = $this->getCategoryLoadById($product->getCategoryIds()[0]);
            $catName = $category->getName();
        }

        if ($product->getTypeId() == 'configurable') {
            $basePrice = $product->getPriceInfo()->getPrice('regular_price');
            $regularPrice = $basePrice->getMinRegularAmount()->getValue();
            $specialPrice = $product->getFinalPrice();
            if (empty($regularPrice)) {
                $regularPrice = $product->getData('price');
                $specialPrice = $product->getData('special_price');
            }
        } else {
            $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
            $specialPrice = $product->getPriceInfo()->getPrice('special_price')->getValue();
        }

        $productTypeInstance = $product->getTypeInstance();
        $isInStock = false;
        if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            $usedProducts = $productTypeInstance->getUsedProducts($product);
            $atrrData = [];
            foreach ($usedProducts as $child) {
                $color = ($child->getColor()) ? $child->getAttributeText('color'): '';
                $size = ($child->getQaSize()) ? $child->getAttributeText('qa_size'): '';
                $atrrData = ['color' => $color, 'size' => $size];
                $productStockObj = $this->stockRegistry->getStockItem($child->getId());
                if ($productStockObj->getIsInStock()) {
                    $isInStock = true;
                    break;
                }
            }
        } else {
            $isInStock = $this->getStockQty($product->getId());
        }

        $returnData = [
            'sku' => $product->getSku(),
            'name' => $product->getName(),
            'productId' => (int)$product->getId(),
            'category' => $catName,
            'brand' => ($product->getBrands()) ? $product->getAttributeText('brands'): '',
            'color' => ((isset($atrrData['color']) && $atrrData['color']) ? $atrrData['color'] :''),
            'size' => ((isset($atrrData['size']) && $atrrData['size']) ? $atrrData['size']:''),
            'quantity' => ($isInStock) ? 1 : 0,
            'currencyCode' => $this->getCurrencyCode(),
            'priceTotal' => (!empty($regularPrice)) ? floatval(number_format(($regularPrice),'2','.')) : $regularPrice,
            'discountAmount' => floatval(number_format(($specialPrice ? $regularPrice - $specialPrice: 0),'2','.')),
            'unitOfMeasureCode' => 'ft'
        ];

        return $returnData;
    }

    /**
     * get loggedin customer data
     * @return json
     */
    public function getLoggedInCustomerData($customerData)
    {
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $publicCookieMetadata->setDuration(30);
        $publicCookieMetadata->setPath('/');
        $publicCookieMetadata->setHttpOnly(false);

        if(!empty($customerData)) {
            $data = [
                'page' =>  [
                    "pageInfo" => [
                        "country" => $this->getDefaultCountry(),
                        "region" => $this->vaimoConfig->getLoggedInRegion(),
                        "language" => $this->getStoreLanguage()
                    ]
                ],
                'userInfo' => [
                    self::USER_HASHED_ID => $this->encryptor->hash($customerData['customer_id']),
                    self::USER_LOGGED_IN => $this->getCustomerIsLoggedIn(),
                    self::LOGGED_IN_SITE => $this->dataLayerConfig->getLoggedInSite(),
                    self::LOGGED_IN_SITE_LANGUAGE => $this->getStoreLanguage(),
                    self::LOGGED_IN_REGION => $this->dataLayerConfig->getLoggedInRegion(),
                    self::LOGGED_IN_COUNTRY => \strtoupper($this->getDefaultCountry())
                ]
            ];

            $this->cookieManager->setPublicCookie(
                'signIn',
                json_encode($data),
                $publicCookieMetadata
            );
        }
    }

    /**
     * get loggedin customer data
     */
    public function getSignedUpCustomerData($customerData)
    {
        $userInfo = $this->getUserInfo();

        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $publicCookieMetadata->setDuration(30);
        $publicCookieMetadata->setPath('/');
        $publicCookieMetadata->setHttpOnly(false);

        if(!empty($customerData)) {
            $data = [
                'page' =>  [
                    "pageInfo" => [
                        "country" => $this->getDefaultCountry(),
                        "region" => $this->vaimoConfig->getLoggedInRegion(),
                        "language" => $this->getStoreLanguage()
                    ]
                ],
                'userInfo' => [
                    self::USER_HASHED_ID => $this->encryptor->hash($customerData['customer_id']),
                    self::USER_LOGGED_IN => $this->getCustomerIsLoggedIn(),
                    self::LOGGED_IN_SITE => $this->dataLayerConfig->getLoggedInSite(),
                    self::LOGGED_IN_SITE_LANGUAGE => $this->getStoreLanguage(),
                    self::LOGGED_IN_REGION => $this->dataLayerConfig->getLoggedInRegion(),
                    self::LOGGED_IN_COUNTRY => \strtoupper($this->getDefaultCountry())
                ]
            ];

            $this->cookieManager->setPublicCookie(
                'signUp',
                json_encode($data),
                $publicCookieMetadata
            );
        }
    }

    /**
     * get base currency code
     * @return json
     */
    public function getCurrencyCode()
    {
        return $this->storeManager->getStore()->getBaseCurrencyCode();
    }

    /**
     * set data for add to cart data layer
     * @return json
     */
    public function getAddToCartEvent($productData, $params, $product)
    {
        $userInfo = $this->getUserInfo();

        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $publicCookieMetadata->setDuration(30);
        $publicCookieMetadata->setPath('/');
        $publicCookieMetadata->setHttpOnly(false);

        if(!empty($productData)) {
            $this->cookieManager->setPublicCookie(
                'cartAdd',
                json_encode($productData),
                $publicCookieMetadata
            );
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return array
     */
    public function getCurrentCategory()
    {
        return $this->registry->registry('current_category');
    }

    /**
     * set data remove items from cart
     * @return json
     */
    public function getRemoveItemCartEvent($data)
    {
        $userInfo = $this->getUserInfo();

        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $publicCookieMetadata->setDuration(20);
        $publicCookieMetadata->setPath('/');
        $publicCookieMetadata->setHttpOnly(false);

        if($data){
            $this->cookieManager->deleteCookie(
                'cartAdd',$publicCookieMetadata);

            $this->cookieManager->deleteCookie(
                'cartRemove', $publicCookieMetadata);
            $this->cookieManager->setPublicCookie(
                'cartRemove',
                json_encode(['remove' => $data['status'], 'data' => $data['item']]),
                $publicCookieMetadata
            );
        }
    }

    /**
     * set order placed data into data layer
     * @param $orderData
     * @return json
     */
    public function setOrderDataEvent($orderData)
    {
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $publicCookieMetadata->setDuration(120);
        $publicCookieMetadata->setPath('/');
        $publicCookieMetadata->setHttpOnly(false);

        if(!empty($orderData)){
            $this->cookieManager->setPublicCookie(
                'orderPlaced',
                json_encode($orderData),
                $publicCookieMetadata
            );
        }
    }

    /**
     * set payment order placed data into data layer
     * @param $paymentData
     */
    public function setPaymentOrderDataEvent($paymentData)
    {
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $publicCookieMetadata->setDuration(10);
        $publicCookieMetadata->setPath('/');
        $publicCookieMetadata->setHttpOnly(false);

        if(!empty($paymentData)){
            $this->cookieManager->deleteCookie(
                'checkoutPayment',$publicCookieMetadata);
            $this->cookieManager->setPublicCookie(
                'checkoutPayment',
                json_encode($paymentData),
                $publicCookieMetadata
            );
        }
    }

    /**
     * set add to wishhList data layer
     * @param $wishlistItem
     * @return json
     */
    public function setViewWishListEvent($wishlistItems)
    {
        if(!empty($wishlistItems)){
            foreach ($wishlistItems as $items) {
                $product = $this->loadProductById($items->getProductId());

                $catName = '';
                if($product->getCategoryIds() && isset($product->getCategoryIds()[0])){
                    $category = $this->getCategoryLoadById($product->getCategoryIds()[0]);
                    $catName = $category->getName();
                }
                $wishListData[] = [
                    'sku' => $product->getSku(),
                    'name' => $product->getName(),
                    'productId' => (int)$items->getProductId(),
                    'brand' => ($product->getBrands()) ? $product->getAttributeText('brands'): '',
                    'color' => ($product->getColor()) ? $product->getAttributeText('color'): '',
                    'size' => ($product->getSize()) ? $product->getAttributeText('size'): '',
                    'quantity' => (int)$product->getQty(),
                    'currencyCode' => $this->getCurrencyCode(),
                    'priceTotal' => floatval(number_format(($product->getPrice()),'2','.')),
                    'discountAmount' => floatval(number_format(($product->getSpecialPrice() ? $product->getPrice() - $product->getSpecialPrice(): 0),'2','.')),
                    'unitOfMeasureCode' => 'ft',
                ];
            }
        }

        if(!empty($wishListData)){
            $this->cookieManager->setPublicCookie(
                'wishlistView',
                json_encode($wishListData),
                $this->getPublicCookieMetadata()
            );
        }
    }

    /**
     * set add to wishhList data layer
     * @param $wishlistItem
     * @return json
     */
    public function setAddToWishListEvent($wishlistItem)
    {

        if(!empty($wishlistItem)){
            $this->cookieManager->deleteCookie(
                'wishlistRemove', $this->getMetadata());
            $this->cookieManager->setPublicCookie(
                'wishlistAdd',
                json_encode($wishlistItem[0]),
                $this->getPublicCookieMetadata()
            );
        }
    }

    /**
     * set remove wishlist data layer
     * @param $wishlistId
     * @return json
     */
    public function setRemoveWishListData($returnData)
    {
        if(!empty($returnData)){
            $this->cookieManager->deleteCookie(
                'wishlistAdd', $this->getMetadata());
            $this->cookieManager->setPublicCookie(
                'wishlistRemove',
                json_encode($returnData),
                $this->getPublicCookieMetadata()
            );
        }
    }

    public function getCategoryLoadById($categoryId)
    {
        return $this->categoryFactory->create()->load($categoryId);
    }

    public function getPublicCookieMetadata()
    {
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $publicCookieMetadata->setDuration(30);
        $publicCookieMetadata->setPath('/');
        $publicCookieMetadata->setHttpOnly(false);

        return $publicCookieMetadata;
    }

    /**
     * Retrieve stock qty whether product
     *
     * @param int $productId
     * @param int $websiteId
     * @return float
     */
    public function getStockQty($productId, $websiteId = null)
    {
        return $this->stockState->getStockQty($productId, $websiteId);
    }

    /**
     * set search Data into datalayer
     * @param arr $searchDat
     */
    public function setInternalSearchEvent($searchData)
    {
        if(!empty($searchData)){
            $this->cookieManager->setPublicCookie(
                $searchData['event'],
                json_encode(
                    [
                        'event' => $searchData['event'],
                        'keyword' => $searchData['keyword'],
                        'page' => $this->getCustomerDataLayer()['pageInfo'],
                        'userInfo' => $this->getCustomerDataLayer()['userInfo']
                    ]
                ),
                $this->getPublicCookieMetadata()
            );
        }
    }

    /**
     * set payment order placed data into data layer
     * @param $shippingData
     */
    public function setShipingDataEvent($shippingData)
    {
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $publicCookieMetadata->setDuration(10);
        $publicCookieMetadata->setPath('/');
        $publicCookieMetadata->setHttpOnly(false);

        if(!empty($shippingData)){
            $this->cookieManager->deleteCookie(
                'checkoutShipping',$publicCookieMetadata);
            $this->cookieManager->setPublicCookie(
                'checkoutShipping',
                json_encode($shippingData),
                $publicCookieMetadata
            );
        }
    }
    /**
     * set newsletter subscription data into data layer
     * @param $shippingData
     */
    public function setIsSubscribedData($isSubscribed)
    {
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $publicCookieMetadata->setDuration(10);
        $publicCookieMetadata->setPath('/');
        $publicCookieMetadata->setHttpOnly(false);

        if(!empty($isSubscribed)){
            $this->cookieManager->setPublicCookie(
                'subscribe',
                json_encode(['is_subscribed' => $isSubscribed]),
                $publicCookieMetadata
            );
        }
    }

    /**
     * get category information by id
     * @return array
     */
    public function getCategoryInfoById()
    {
        $catInfo = [];
        if($this->getCurrentCategory() && $this->getCurrentCategory()->getId()) {
            $categoryId = $this->getCurrentCategory()->getId();
            $category = $this->getCategoryLoadById($categoryId);
            if($category){
                $collection = $category->getResourceCollection();
                $pathIds = $category->getPathIds();
                $collection->addAttributeToSelect('name');
                $collection->addAttributeToSelect('url_key');
                $collection->addAttributeToFilter('entity_id', array('in' => $pathIds));
                $collection->addAttributeToFilter('level', array('nin' => ['0', '1']));
                $catName = [];
                foreach ($collection as $cat) {
                    $catName[] = $cat->getUrlKey();
                }

                $siteSection = $siteSubSection1 = $siteSubSection2 = $siteSubSection3 = '';
                if(!empty($catName)) {

                    if(isset($catName[0]) && !empty($catName[0])) {
                        $siteSection = $catName[0];
                    }

                    if(isset($catName[1]) && !empty($catName[1])) {
                        $siteSubSection1 = $catName[1];
                    }

                    if(isset($catName[2]) && !empty($catName[2])) {
                        $siteSubSection2 = $catName[2];
                    }

                    if(isset($catName[3]) && !empty($catName[3])) {
                        $siteSubSection3 = $catName[3];
                    }
                }

                $catInfo = [
                    "siteSection" => $siteSection,
                    "siteSubSection1" => $siteSubSection1,
                    "siteSubSection2" => $siteSubSection2,
                    "siteSubSection3" => $siteSubSection3
                ];
            }
        }

        return $catInfo;
    }

    /**
     * @param $path
     * @param $storeId
     * @return mixed
     */
    protected function getConfigValue($path): mixed
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue($path, $storeScope);
    }

    /**
     * @return mixed
     */
    public function isEnabledAdobeLaunch(): mixed
    {
        $isEnable  = $this->getConfigValue(self::IS_ENABLE_ADOBELAUNCH);
        return $isEnable;
    }

    /**
     * @return string
     *
     */
    public function getStoreLanguage()
    {
        return $this->getConfigValue(self::SITE_LANGAUGE);
    }

    /**
     * @return string
     *
     */
    public function getMetadata()
    {
        $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $metadata->setPath('/');

        return $metadata;
    }
    /**
     * get product by sku
     *  @return obj
     *
     */
    public function getProductBySku($sku)
    {
        return $this->productRepository->get($sku);;
    }
}
