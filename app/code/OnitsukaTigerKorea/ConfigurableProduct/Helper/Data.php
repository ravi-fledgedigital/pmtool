<?php

namespace OnitsukaTigerKorea\ConfigurableProduct\Helper;

use Magento\Eav\Model\Config;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Swatches\Model\SwatchAttributesProvider;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use OnitsukaTiger\Store\Model\Store;
use Magento\Framework\App\ResourceConnection;
use OnitsukaTiger\Store\Helper\Data as HelperDataStore;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

/**
 * Class Data
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var SwatchAttributesProvider|mixed
     */
    protected $swatchAttributesProvider;

    /**
     * @var \OnitsukaTiger\Fixture\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var CollectionFactory
     */
    protected $productCollections;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var HelperDataStore
     */
    private $helperStore;

    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var GetSourceItemBySourceCodeAndSku
     */
    private $getSourceItemBySourceCodeAndSku;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSaveInterface;

    /**
     * @var Config
     */
    protected $eavConfig;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory $productCollections
     * @param ResourceConnection $resourceConnection
     * @param HelperDataStore $helperStore
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku
     * @param SourceItemsSaveInterface $sourceItemsSaveInterface
     * @param Config $eavConfig
     */
    public function __construct(
        ProductRepositoryInterface                         $productRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        CollectionFactory                                  $productCollections,
        HelperDataStore                                    $helperStore,
        ResourceConnection                                 $resourceConnection,
        OrderItemRepositoryInterface                       $orderItemRepository,
        GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
        \OnitsukaTiger\Fixture\Helper\Data                 $helper,
        Config                                             $eavConfig,
        SwatchAttributesProvider                           $swatchAttributesProvider = null,
        \Magento\Framework\Json\EncoderInterface           $jsonEncoder,
        \Magento\Framework\App\Helper\Context              $context
    )
    {
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
        $this->productCollections = $productCollections;
        $this->helperStore = $helperStore;
        $this->resourceConnection = $resourceConnection;
        $this->orderItemRepository = $orderItemRepository;
        $this->helper = $helper;
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->jsonEncoder = $jsonEncoder;
        $this->eavConfig = $eavConfig;
        $this->swatchAttributesProvider = $swatchAttributesProvider
            ?: ObjectManager::getInstance()->get(SwatchAttributesProvider::class);
        parent::__construct($context);
    }

    /**
     * @param $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function enableShowSizeForDisplay($storeId = null)
    {
        if ($storeId) {
            $storeId = $this->helper->getCurrentStore()->getId();
        }
        return $this->scopeConfig->getValue('onitsukatiger_catalog_product_attribute/product_show_size_for_display/enable', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $sku
     * @param $storeId
     * @return string
     */
    public function getSizeForDisplay($sku, $storeId = null): string
    {
        if ($this->enableShowSizeForDisplay($storeId)) {
            try {
                $product = $this->productRepository->get($sku);
                if ($product->getSizeForDisplay()) {
                    return $product->getSizeForDisplay();
                }
            } catch (\Exception $exception) {
                return '';
            }
        }
        return '';
    }

    /**
     * @param $sku
     * @param $sourceCode
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws ValidationException
     */
    public function updateSkuWms($sku,$sourceCode)
    {
        $storeId = $this->helperStore->getStoreIdFromSourceCode($sourceCode);
        $product = $this->productRepository->get($sku,true);
        /*if ($storeId == Store::KO_KR && $product->getNextSkuWms()) {
            $nextSkuWms = $this->getNextSkuWms($product);
            $product->setData('sku_wms',trim($nextSkuWms));
            $product->save();
            $sourceItem = $this->getSourceItemBySourceCodeAndSku->execute($sourceCode, $product->getSku());
            $sourceItem->setQuantity(0);
            $this->sourceItemsSaveInterface->execute([$sourceItem]);
            $this->deleteReservationBySku($product->getSku(), $storeId);
        }*/
    }

    /**
     * Get element off the beginning of list NextSkuWms
     * @param $product
     * @return string
     */
    public function getNextSkuWms($product)
    {
        if(!empty($product->getNextSkuWms())){
            $nextSkuWmsArr = explode(',', $product->getNextSkuWms());
            $currentSkuWms = $product->getSkuWms();
            $index = array_search($currentSkuWms,$nextSkuWmsArr);
            $nextSkuWms = $nextSkuWmsArr[0];
            if(!$index) {
                if(count($nextSkuWmsArr) == 1) {
                    return $nextSkuWmsArr[0];
                }
                if($index === 0) {
                    return $nextSkuWmsArr[1];
                }
                return $nextSkuWms;
            }else{
                if($index != array_key_last($nextSkuWmsArr)) {
                    $nextSkuWms = $nextSkuWmsArr[$index+1];
                }
            }
            return $nextSkuWms;
        }
        return '';
    }

    /**
     * @param $item
     * @return false|\Magento\Sales\Api\Data\OrderItemInterface
     * @throws NoSuchEntityException
     */
    public function checkProductSkuWms($itemReturn)
    {
        $orderItem = $this->orderItemRepository->get($itemReturn->getOrderItemId());
        /*if ($itemReturn->getStoreId() == Store::KO_KR) {
            $product = $this->productRepository->get($itemReturn->getSku(), true, Store::KO_KR);
            if ($orderItem->getSkuWms() !== $product->getSkuWms()) {
                return false;
            }
        }*/
        return $orderItem;
    }

    /**
     * @param string $sku
     * @param $stockId
     * @return void
     */
    private function deleteReservationBySku(string $sku, $stockId)
    {
        $table = $this->resourceConnection->getTableName('inventory_reservation');
        $connect = $this->resourceConnection->getConnection();
        $condition = ['sku =?' => $sku,'stock_id =?' => $stockId];
        $connect->delete($table, $condition);
    }
}
