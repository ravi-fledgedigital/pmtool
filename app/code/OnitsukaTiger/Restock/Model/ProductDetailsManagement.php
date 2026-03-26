<?php

namespace OnitsukaTiger\Restock\Model;

use OnitsukaTiger\Restock\Api\ProductDetailsManagementInterface;
use OnitsukaTiger\Restock\Model\ReservationOptions as ReservationFlag;
use Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory as WishlistCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection;
use Magento\CatalogInventory\Api\StockStateInterface;

class ProductDetailsManagement implements ProductDetailsManagementInterface
{

    protected $productRepository;
    protected $stockFactory;
    protected $customerSession;
    protected $attribute;
    protected $wishlistCollectionFactory;
    protected $productCollection;
    protected $productAction;
    protected $storeManager;
    protected $resourceConnection;
    protected $dataCollection;
    protected $stockState;

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productRepository,
        \Magento\ProductAlert\Model\Stock $stockFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $attribute,
        WishlistCollectionFactory $wishlistCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
        \Magento\Catalog\Model\Product\Action $productAction,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ResourceConnection $resourceConnection,
        Collection $dataCollection,
        StockStateInterface $stockState
    ) {
        $this->productRepository = $productRepository;
        $this->stockFactory = $stockFactory;
        $this->customerSession = $customerSession;
        $this->attribute = $attribute;
        $this->wishlistCollectionFactory = $wishlistCollectionFactory;
        $this->productCollection = $productCollection;
        $this->productAction = $productAction;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->dataCollection = $dataCollection;
        $this->stockState = $stockState;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductDetails($productId)
    {
        $product = $this->productRepository->create()->load($productId);

         

        // Reserved Notification Flag
        /*   0: Sold Out
             1 (Default Value): Restock Notification
             2: Pre-Order Request
        */
             $stockQtyResponse = '';
             $restockFlagResponse = '';
             $statusFlag = '';
             $reservedStatusResponse = '';
             $reservedFlagResponse = '';
             $reservedLabelResponse = '';
             $reservationEstimatedShippingResponse = "";
             $reservationPreSaleSartResponse = "";
             $reservationPeriodEndedResponse = "";
        
             
             $isAllComingSoon = false;

            // Stock Qty
            $stockQty = $this->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
            $stockQtyResponse = $stockQty;
            

            //Restock Notification Flag
            $restockFlag = ($product->getRestockNotificationFlag() == 2) ? $product->getRestockNotificationFlag() : 0;
            //$product->getRestockNotificationFlag();
            $restockFlagResponse = is_null($restockFlag) ? 0 : $restockFlag;
            $statusFlag = $product->getStatus() == 2 ? 0 : 1;

            // Pre-Order Flag
            $reservedStatusResponse = $this->preOrderStatus($product);
            $reservedFlagResponse = ($product->getAttributeText('reservation_flag') == ReservationFlag::RESERVATION_PRE_ORDER) ? true : false;
            $reservedLabelResponse = $this->preOrderStatus($product) ? "Reserve" : "Pre-sale";
            $reservationEstimatedShippingResponse = ($this->preShippingDate($product)) ? $this->preShippingDate($product) : 0;
            date_default_timezone_set("Asia/Tokyo");
        if ($product->getReservationFrom()) {

            $finalDate = date("Y年n月j日", strtotime($product->getReservationFrom()));
            $reservationPreSaleSartResponse = $finalDate;
            $reservationPeriodEndedResponse = $this->preOrderPeriodEnded($product);
        }
            
        

        return [
            'stockQuantity' => $stockQtyResponse,
            'restockFlag' => $restockFlag,
            'statusFlag' => $statusFlag,
            'reservedStatus' => $reservedStatusResponse,
            'reservedFlag' => $reservedFlagResponse,
            'reservedLabel' => $reservedLabelResponse,
            'reservedEstimatedShipping' => $reservationEstimatedShippingResponse,
            'reservedPreSaleStart' => $reservationPreSaleSartResponse,
            'reservationPeriodEnded' => $reservationPeriodEndedResponse
        ];
    }

    public function preOrderStatus($child, $updateStatus = false)
    {
        $reservationFlag = ($child->getAttributeText('reservation_flag') == ReservationFlag::RESERVATION_PRE_ORDER) ? true : false;
        if ($reservationFlag && $child->getReservationFrom() != '') {
            date_default_timezone_set("Asia/Tokyo");
            $today = date('Y-m-d H:i:s');
            $from = date('Y-m-d H:i:s', strtotime($child->getReservationFrom()));
            $to = date('Y-m-d H:i:s', strtotime($child->getReservationTo()));

            if ($today >= $from && $today <= $to) {
                return true;
            } elseif ($today >= $to) {
                if ($updateStatus) {
                    $this->productAction->updateAttributes(
                        $this->preOrderHelper->getIds($child->getColorCode(), $child->getPartNo()),
                        [
                            'reservation_flag' => $this->preOrderHelper->getNormalFlagId()
                        ],
                        $this->storeManager->getStore()->getId()
                    );
                    $this->preOrderStatus($child);
                }
            }
        }
        return false;
    }


    public function getStockQty($productId, $websiteId = null)
    {
        return $this->stockState->getStockQty($productId, $websiteId);
    }

    public function preShippingDate($product)
    {
        date_default_timezone_set("Asia/Tokyo");
        $estimatedShipping = $product->getEstimatedShipping() ?? false;
        $period = "";
        
        if (!$estimatedShipping) {
            return false;
        }

        switch ($value = date("d", strtotime($estimatedShipping))) {
            case (1 <= $value) && ($value <= 10):
                $period = "Early";
                break;
            case (11 <= $value) && ($value <= 20):
                $period = "Mid";
                break;
            case (21 <= $value) && ($value <= 31):
                $period = "Late";
                break;
        }
        $finalYear = date('Y年', strtotime($estimatedShipping));
        $finalMonth = date('M月', strtotime($estimatedShipping));
        return $finalYear.'.'.$period.'-'.$finalMonth;
    }

    public function preOrderPeriodEnded($product)
    {
        $reservationFlag = ($product->getAttributeText('reservation_flag') == ReservationFlag::RESERVATION_PRE_ORDER) ? true : false;
        if ($reservationFlag) {
            date_default_timezone_set("Asia/Tokyo");
            $today = date('Y-m-d H:i:s');
            $to = $product->getReservationTo() ?? date('Y-m-d');
            $to = date('Y-m-d H:i:s', strtotime($to));
            $estimatedShipping = $product->getEstimatedShipping();
            if ($estimatedShipping) {
                $estimatedShipping = date('Y-m-d H:i:s', strtotime($estimatedShipping));
            } else {
                return false;
            }
            if ($today >= $to) {
                return true;
            }
        }

        return false;
    }
}
