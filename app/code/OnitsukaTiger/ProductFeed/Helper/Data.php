<?php
namespace OnitsukaTiger\ProductFeed\Helper;

use Exception;
use Liquid\Template;
use Magento\Backend\Model\Session;
use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Config\Model\ResourceModel\Config as ModelConfig;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Filesystem\Io\Ftp;
use Magento\Framework\Filesystem\Io\Sftp;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Url as UrlAbstract;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\Review\Model\Rating\Option\VoteFactory;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Review\Model\Review\SummaryFactory;
use Magento\Review\Model\ReviewFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Mageplaza\ProductFeed\Block\Adminhtml\LiquidFilters;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Mageplaza\ProductFeed\Helper\Mapping;
use Mageplaza\ProductFeed\Model\FeedFactory;
use Mageplaza\ProductFeed\Model\HistoryFactory;
use OnitsukaTiger\ProductFeed\CatalogInventory\Helper\Stock;
use OnitsukaTiger\Catalog\Helper\ConfigurablePrice;
use OnitsukaTiger\Logger\Logger;

class Data extends \Mageplaza\ProductFeed\Helper\Data
{
    /**
     * @var Stock
     */
    private $stockHelper;

    /**
     * @var GetReservationsQuantityInterface
     */
    private $getReservationsQuantity;

    /**
     * @var ConfigurablePrice
     */
    private $catalogHelperData;

    private $getProductSalableQty;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var Logger
     */
    protected $logger;

    protected $productAttributeCollectionFactory;

    /**
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param ProductFactory $productFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param UrlInterface $backendUrl
     * @param Ftp $ftp
     * @param Sftp $sftp
     * @param ManagerInterface $messageManager
     * @param TransportBuilder $transportBuilder
     * @param DateTime $date
     * @param TimezoneInterface $timezone
     * @param Resolver $resolver
     * @param File $file
     * @param ReviewFactory $reviewFactory
     * @param SummaryFactory $reviewSummaryFactory
     * @param StockRegistryInterface $stockState
     * @param LiquidFilters $liquidFilters
     * @param HistoryFactory $historyFactory
     * @param FeedFactory $feedFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param CollectionFactory $prdAttrCollectionFactory
     * @param UrlFinderInterface $urlFinder
     * @param Session $session
     * @param DriverFile $driverFile
     * @param UrlAbstract $urlModel
     * @param CurlFactory $curlFactory
     * @param EncryptorInterface $encryptor
     * @param ModelConfig $modelConfig
     * @param Mapping $helperMapping
     * @param ConfigCollectionFactory $configCollectionFactory
     * @param ProductRepository $productRepository
     * @param CatalogHelper $catalogHelper
     * @param Escaper $escaper
     * @param DirectoryList $directoryList
     * @param Curl $curl
     * @param ProductCollection $productCollection
     * @param UrlRewriteCollection $urlRewriteCollection
     * @param ReviewCollectionFactory $reviewCollectionFactory
     * @param ProductMetadataInterface $productMetadata
     * @param VoteFactory $voteFactory
     * @param CollectionFactory $productAttributeCollectionFactory
     * @param Stock $stockHelper
     * @param GetReservationsQuantityInterface $getReservationsQuantity
     * @param ConfigurablePrice $catalogHelperData
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        ProductFactory $productFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        UrlInterface $backendUrl,
        Ftp $ftp,
        Sftp $sftp,
        ManagerInterface $messageManager,
        TransportBuilder $transportBuilder,
        DateTime $date,
        TimezoneInterface $timezone,
        Resolver $resolver,
        File $file,
        ReviewFactory $reviewFactory,
        SummaryFactory $reviewSummaryFactory,
        StockRegistryInterface $stockState,
        LiquidFilters $liquidFilters,
        HistoryFactory $historyFactory,
        FeedFactory $feedFactory,
        PriceCurrencyInterface $priceCurrency,
        CollectionFactory $prdAttrCollectionFactory,
        UrlFinderInterface $urlFinder,
        Session $session,
        DriverFile $driverFile,
        UrlAbstract $urlModel,
        CurlFactory $curlFactory,
        EncryptorInterface $encryptor,
        ModelConfig $modelConfig,
        Mapping $helperMapping,
        ConfigCollectionFactory $configCollectionFactory,
        ProductRepository $productRepository,
        CatalogHelper $catalogHelper,
        Escaper $escaper,
        DirectoryList $directoryList,
        Curl                             $curl,
        ProductCollection                $productCollection,
        UrlRewriteCollection             $urlRewriteCollection,
        ReviewCollectionFactory          $reviewCollectionFactory,
        ProductMetadataInterface         $productMetadata,
        VoteFactory                      $voteFactory,
        CollectionFactory                $productAttributeCollectionFactory,
        Stock                            $stockHelper,
        GetReservationsQuantityInterface $getReservationsQuantity,
        ConfigurablePrice                $catalogHelperData,
        GetProductSalableQtyInterface    $getProductSalableQty,
        Logger                           $logger
    )
    {
        $this->productAttributeCollectionFactory  = $productAttributeCollectionFactory;
        $this->stockHelper = $stockHelper;
        $this->getReservationsQuantity = $getReservationsQuantity;
        $this->catalogHelperData = $catalogHelperData;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->_productFactory = $productFactory;
        $this->logger = $logger;
        parent::__construct($context, $objectManager, $storeManager, $productFactory, $categoryCollectionFactory, $backendUrl, $ftp, $sftp, $messageManager, $transportBuilder, $date, $timezone, $resolver, $file, $reviewFactory, $reviewSummaryFactory, $stockState, $liquidFilters, $historyFactory, $feedFactory, $priceCurrency, $prdAttrCollectionFactory, $urlFinder, $session, $driverFile, $urlModel, $curlFactory, $encryptor, $modelConfig, $helperMapping, $configCollectionFactory, $productRepository, $catalogHelper, $escaper, $directoryList, $curl, $productCollection, $urlRewriteCollection, $reviewCollectionFactory, $productMetadata, $voteFactory);
    }

    /**
     * @Override
     * @param $feed
     * @param array $productAttributes
     * @param array $productIds
     * @param bool $isSync
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getProductsData($feed, $productAttributes = [], $productIds = [], $isSync = false)
    {
        $categoryMap = $this->unserialize($feed->getCategoryMap());
        $storeId     = $feed->getStoreId() ?: $this->storeManager->getDefaultStoreView()->getId();

        $allCategory = $this->categoryCollectionFactory->create();
        $allCategory->setStoreId($storeId)->addAttributeToSelect('name');
        $categoriesName = [];
        /** @var $item Category */
        foreach ($allCategory as $item) {
            $categoriesName[$item->getId()] = $item->getName();
        }

        $allSelectProductAttributes = $this->prdAttrCollectionFactory->create()
            ->addFieldToFilter('frontend_input', ['in' => ['multiselect', 'select']])
            ->getColumnValues('attribute_code');

        $this->storeManager->setCurrentStore($feed->getStoreId());

        $matchingProductIds = !empty($productIds) ? $productIds : $feed->getMatchingProductIds();
        if ($isSync) {
            $productCollection = $this->productCollection->create()->addUrlRewrite()
                ->addAttributeToSelect('*')->addStoreFilter($storeId)
                ->addFieldToFilter('entity_id', ['in' => $matchingProductIds])->addMediaGalleryData();
        } else {
            $productCollection = $this->productCollection->create()->addUrlRewrite()
                ->addAttributeToSelect($productAttributes)
                ->addAttributeToSelect(['price', 'status', 'image', 'material_code'])
                ->addStoreFilter($storeId)
                ->addFieldToFilter('entity_id', ['in' => $matchingProductIds])->addMediaGalleryData();
        }
        $this->stockHelper->addStockDataToCollection($productCollection);
        $websiteId = $this->catalogHelperData->getWebsiteIdByStoreId($feed->getStoreId());

        $result = [];
        /** @var $product Product */
        foreach ($productCollection as $key => $product) {
            if($product->getTypeId() == Configurable::TYPE_CODE){
                $minimalPrice = $this->catalogHelperData->getMinimalPrice($product, $websiteId);
                if ($minimalPrice['price'] == null) {
                    $productCollection->removeItemByKey($key);
                    continue;
                }
                $product->setPrice($minimalPrice['price']);
                $product->setSpecialPrice($minimalPrice['special_price']);
            }
            $typeInstance           = $product->getTypeInstance();
            $childProductCollection = $typeInstance->getAssociatedProducts($product);
            if ($childProductCollection) {
                $associatedData = [];
                foreach ($childProductCollection as $item) {
                    $associatedData = $item->getData();
                }
                $product->setAssociatedProducts($associatedData);
            } else {
                $product->setAssociatedProducts([]);
            }

            $stockItem         = $this->stockState->getStockItem(
                $product->getId(),
                $feed->getStoreId()
            );
            $qty               = $stockItem->getQty();
            $categories        = $product->getCategoryCollection()->addAttributeToSelect('*');
            $relatedProducts = [];
            foreach ($product->getRelatedProducts() as $item) {
                $relatedProducts[] = $item->getData();
            }
            $crossSellProducts = [];
            foreach ($product->getCrossSellProducts() as $item) {
                $crossSellProducts[] = $item->getData();
            }
            $upSellProducts = [];
            foreach ($product->getUpSellProducts() as $item) {
                $upSellProducts[] = $item->getData();
            }

            try {
                $oriProduct = $this->productRepository->getById($product->getId(), false, $storeId);
            } catch (Exception $e) {
                $oriProduct = $this->productFactory->create()->setStoreId($storeId)->load($product->getId());
            }

            if ($oriProduct->getResource()->getAttribute('material')) {
                $material = $oriProduct->getAttributeText('material');
                if (is_array($material)) {
                    $material = implode('/', $material);
                }
                $product->setData('material', $material);
            }
            $tierPrices = $product->getTierPrice();
            $tierPrice  = [];
            if (count($tierPrices) > 0) {
                foreach ($tierPrices as $price) {
                    $tierPrice[] = 'price_qty: ' . $price['price_qty'] . ', price: ' . $price['price'];
                }
                $tierPrice = implode('; ', $tierPrice);
            }
            $finalPrice = $this->getProductPrice($oriProduct);
            $finalPrice = $this->catalogHelper->getTaxPrice($oriProduct, $finalPrice, true);
            $finalPrice = $this->convertPrice($finalPrice, $storeId);

            $productLink = $this->getProductUrl($product, $storeId);
            if ($this->getConfigGeneral('reports')) {
                $productLink .= $this->getCampaignUrl($feed);
            }

            $imageLink = '';
            if ('no_selection' !== $product->getImage()) {
                $imageLink = $this->scopeConfig->getValue(
                        'web/secure/base_media_url', ScopeInterface::SCOPE_STORE, $feed->getStoreId()
                    ) . 'catalog/product' . $product->getImage();
            }

            $images    = $oriProduct->getMediaGalleryImages()->getSize() ?
                $oriProduct->getMediaGalleryImages() : [[]];
            if (is_object($images)) {
                $imagesData = [];
                foreach ($images->getItems() as $item) {
                    $imagesData[] = $item->getData();
                }
                $images = $imagesData;
            }
            /** @var $category Category */
            $lv           = 0;
            $categoryPath = '';
            $categoryMapPath = '';
            $cat          = new DataObject();
            $categoriesData = [];
            foreach ($categories as $category) {
                if ($lv < $category->getLevel()) {
                    $lv  = $category->getLevel();
                    $cat = $category;
                }
                $categoriesData[] = $category->getData();
            }
            $mapping = '';
            if (isset($categoryMap[$cat->getId()])) {
                $mapping = $categoryMap[$cat->getId()];
            }
            $catPaths = $cat->getPathInStore() ? array_reverse(explode(',', $cat->getPathInStore())) : [];
            foreach ($catPaths as $index => $catId) {
                if ($index === (count($catPaths) - 1)) {
                    $categoryPath .= isset($categoriesName[$catId]) ? $categoriesName[$catId] : '';
                } else {
                    $categoryPath .= (isset($categoriesName[$catId]) ? $categoriesName[$catId] : '') . ' > ';
                }
            }

            $simpleProducts = $collection = $this->_productFactory->create()->getCollection()
                ->addAttributeToSelect('sku')
                ->addAttributeToSelect('type_id')
                ->addAttributeToFilter('type_id',array('eq' => 'simple'))
                ->addAttributeToFilter('sku',array('like' => $product->getSku().'%')
                );
            $productQtyInStock = 0;
            foreach($simpleProducts->getItems() as $simple){
                try {
                    $simpleQty = $this->getProductSalableQty->execute($simple->getSku(), $this->stockHelper->getStockId());
                } catch (SkuIsNotAssignedToStockException $exception) {
                    $simpleQty = 0;
                }
                $productQtyInStock += $simpleQty;
            }

            $productQtyInStock > 0 ? $product->setData('quantity_and_stock_status', 'in stock')
                : $product->setData('quantity_and_stock_status', 'out of stock');

            $noneAttr = [
                'categoryCollection',
                'relatedProducts',
                'crossSellProducts',
                'upSellProducts',
                'final_price',
                'link',
                'image_link',
                'images',
                'category_path',
                'mapping',
                'qty',
            ];

            // Convert attribute value to attribute text
            foreach ($productAttributes as $attributeCode) {
                try {
                    if ($attributeCode === 'quantity_and_stock_status'
                        || in_array($attributeCode, $noneAttr, true)
                        || !in_array($attributeCode, $allSelectProductAttributes, true)
                        || !$product->getData($attributeCode)
                    ) {
                        continue;
                    }
                    $attributeText = $product->getResource()->getAttribute($attributeCode)
                        ->setStoreId($feed->getStoreId())->getFrontend()->getValue($product);
                    if (is_array($attributeText)) {
                        $attributeText = implode(',', $attributeText);
                    }
                    if ($attributeText) {
                        $product->setData($attributeCode, $attributeText);
                    }
                } catch (Exception $e) {
                    $this->logger->critical($e->getMessage());
                    continue;
                }
            }

            $product->setData('categoryCollection', $categories);
            $product->setData('relatedProducts', $relatedProducts);
            $product->setData('crossSellProducts', $crossSellProducts);
            $product->setData('upSellProducts', $upSellProducts);
            $product->setData('final_price', $finalPrice);
            $product->setData('link', $productLink);
            $product->setData('image_link', $imageLink);
            $product->setData('images', $images);
            $product->setData('category_path', $categoryPath);
            $product->setData('mapping', $categoryMapPath);
            $product->setData('qty', $qty);
            $result[] = self::jsonDecode(self::jsonEncode($product->getData()));
        }

        if ($isSync) {
            return $productCollection;
        }

        return $productCollection;
    }
}
