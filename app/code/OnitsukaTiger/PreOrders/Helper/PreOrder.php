<?php
/** phpcs:ignoreFile */
namespace OnitsukaTiger\PreOrders\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\DateTime;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Helper For Pre-Order functionality
 */
class PreOrder extends AbstractHelper
{
    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
    * @var StoreManagerInterface
    */
    private $storeManager;

    protected $_productloader;

    /**
     * @var OrderItemCollectionFactory
     */
    private $orderItemCollectionFactory;

    /**
     * @var Data
     */
    protected $helperIsModuleEnable;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @param Context $context
     * @param Collection $collection
     * @param DateTime $dateTime
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     */
    public function __construct(
        Context $context,
        DateTime $dateTime,
        Data $helperIsModuleEnable,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        OrderCollectionFactory $orderCollectionFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
    ) {
        $this->dateTime = $dateTime;
        $this->logger = $logger;
        $this->helperIsModuleEnable = $helperIsModuleEnable;
        $this->_productloader = $_productloader;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->storeManager = $storeManager;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->stockRegistry = $stockRegistry;
        parent::__construct($context);
    }

    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @param int $productId
     * @param int $storeId
     * @return bool
     * @throws LocalizedException
     */
    public function isProductPreOrder($productId, $storeId = 0)
    {
        $storeId = (isset($storeId) && $storeId > 0) ? $storeId : $this->getStoreId();

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/pre_order_invoice.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==============is Product Pre Order============storeId: ' . $storeId);

        if ($this->helperIsModuleEnable->isModuleEnabled($storeId)) {
            $product = $this->_productloader->create()->setStoreId($storeId)->load($productId);
            $currentDate = date('Y-m-d');
            $logger->info('Current Date: ' . $currentDate);
            if ($product &&
            $product->getPreOrderStatus() == 1 && $product->getStartDatePreorder() && strtotime($product->getStartDatePreorder()) <= strtotime($currentDate)
            ) {
                $logger->info('getPreOrderStatus: ' . $product->getPreOrderStatus());
                $logger->info('getStartDatePreorder: ' . $product->getStartDatePreorder());
                $logger->info('getEndDatePreorder : ' . $product->getEndDatePreorder());

                if ($product->getEndDatePreorder() && strtotime($product->getEndDatePreorder()) < strtotime($currentDate)) {
                    $logger->info('==============end date============ < strtotime($currentDate):return = false ');
                    return false; // Add to Cart
                }

                $stockitem = $this->stockRegistry->getStockItem($productId, $product->getStore()->getWebsiteId());
                //$logger->info('$stockitem: ' . $stockitem);
                $isBackorder = $stockitem->getBackorders();
                //$logger->info('$isBackorder: ' . $isBackorder);

                //When both Pre-order and Notification options are simultaneously activated for a product, the system
                // will prioritize showing re-stock notifications for KR and pre-orders to customers from SEA
                if ($storeId  == '5' && $this->getRestockFlag($productId) == '2' && !$isBackorder) {
                    return false; // Add to Cart
                    $logger->info('=========return============: false ($storeId  == 5)' );

                }
                $logger->info('--------------return-----------: true' );
                return true; // Pre Order
            }
        }
        $logger->info('==============return============: false' );
        return false; // Add to Cart
    }
    /**
    * Function getRestockFlag
    *
    * @param object $product
    *
    * @return string
    */
    public function getRestockFlag($productId)
    {
        $product = $this->_productloader->create()->load($productId);
        return $product->getRestockNotificationFlag();
    }

    /**
     * @param QuoteItem $quoteItem
     * @return bool
     * @throws LocalizedException
     */
    public function isQuoteItemPreOrder(QuoteItem $quoteItem)
    {
        $isPreOrder = false;
        switch ($quoteItem->getProductType()) {
            case 'bundle':
                foreach ($quoteItem->getChildren() as $child) {
                    $productId = $child->getProductId();
                    $isPreOrder = $this->isProductPreOrder($productId);
                    if ($isPreOrder) {
                        break;
                    }
                }
                break;

            case 'configurable':
                $option = $quoteItem->getOptionByCode('simple_product');
                $productId = $option->getProductId();
                $isPreOrder = $this->isProductPreOrder($productId);
                break;

            default:
                $productId = $quoteItem->getProductId();
                $isPreOrder = $this->isProductPreOrder($productId);
                break;
        }

        return $isPreOrder;
    }

    /**
     * @param int $productId
     * @param int $storeId
     * @return \Magento\Framework\Phrase|string
     * @throws LocalizedException
     */
    public function getPreOrderStatusLabelByProductId($productId)
    {
        $storeId = $this->getStoreId();
        $product = $this->_productloader->create()->setStoreId($storeId)->load($productId);
        $result = __('');
        $currentDate = date('Y-m-d');

        $storeCode = $this->storeManager->getStore()->getCode();

        $preOrder = 'Pre-order item will be shipped out on';
        if ($storeCode == 'web_kr_ko') {
            $preOrder = '해당 제품은';
        } elseif ($storeCode == 'web_th_th') {
            $preOrder = 'สินค้าพรีออเดอร์จะถูกจัดส่งในวันที่';
        }

        $inStockDate = '';
        $preOrderNote = '';

        if ($product &&
            $product->getPreOrderStatus() == 1 && $product->getStartDatePreorder() && strtotime($product->getStartDatePreorder()) <= strtotime($currentDate)) {
            if (!empty($product->getEndDatePreorder())) {
                if ($storeCode == 'web_kr_ko') {
                    $inStockDate = date('Y 년 m 월 d 일', strtotime($product->getEndDatePreorder()));
                } else {
                    $inStockDate = date('M d, Y', strtotime($product->getEndDatePreorder()));
                }
            }
            if ($product->getPreOrderNote()) {
                $preOrderNote =$product->getPreOrderNote();
            }

            if ($storeCode == 'web_kr_ko') {
                $result .= __($preOrderNote);
            } else {
                $result .= __($preOrder . " " . $inStockDate . " " . $preOrderNote);
            }


        }
        return $result;
    }

    /**
    * @param int $orderId
    * @return bool
    */
    public function isOrderContainsPreOrderProducts($orderId)
    {
        $preOrdersCount = $this->orderItemCollectionFactory->create()
            ->addFieldToFilter('order_id', ['eq' => $orderId])
            ->addFieldToFilter('is_pre_order', '1')
            ->getSize();

        return (bool)$preOrdersCount;
    }

    /**
     * @param int $entity_id
     * @return bool
     */
    public function isOrderItemOnPreOrderState($id)
    {
        try {
            $storeId = $this->getStoreId();
            if ($this->helperIsModuleEnable->isModuleEnabled($storeId)) {
                $preOrdersCount = $this->orderCollectionFactory->create()
                ->addFieldToFilter('entity_id', ['eq' => $id])
                ->addFieldToFilter('is_pre_order', '1')
                ->getSize();
                return (bool)$preOrdersCount;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderItemInterface $itemCollection
     * @return \Magento\Framework\DataObject|null
     */
    public function getConfigurableProductChildItem($itemCollection)
    {
        try {
            $itemChildProductCollection = $this->orderItemCollectionFactory->create();
            $itemChildProductCollection
                ->addFieldToFilter('parent_item_id', ['eq' => $itemCollection->getItemId()])
                ->setPageSize(1)
                ->getFirstItem();
            if ($itemChildProductCollection->getSize() == 0) {
                return null;
            }

            return $itemChildProductCollection->getFirstItem();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    public function checkPreOrderForShipment($productId, $storeId = 0)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/pre_order_shipment.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        if ($storeId > 0) {
            $storeId = $storeId;
        } else {
            $storeId = $this->getStoreId();
        }

        $logger->info('order item store id - ' . $storeId);
        if ($this->helperIsModuleEnable->isModuleEnabled($storeId)) {
            $product = $this->_productloader->create()->setStoreId($storeId)->load($productId);
            $logger->info('Store ID used to load product: ' . $storeId);
            $logger->info('Product ID: ' . $productId);
            $logger->info('Pre Order Status: ' . $product->getPreOrderStatus());
            $logger->info('All Data: ' . print_r($product->getData(), true));
            if ($product && $product->getPreOrderStatus() == 1) {
                return true; // Pre Order
            }
        }
        return false;
    }
}
