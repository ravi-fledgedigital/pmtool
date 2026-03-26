<?php
declare(strict_types=1);

namespace OnitsukaTiger\Shipment\Observer;

use Magento\Catalog\Model\Product\Action;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Sales\Model\Order\Shipment;
use Magento\Store\Model\StoreManagerInterface;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;

class StatusUpdate implements ObserverInterface
{
    /**
     * @var ShipmentStatus
     */
    private $shipmentStatusModel;

    /**
     * @var OrderStatus
     */
    private $orderStatusModel;

    /**
     * @var Action
     */
    private $action;

    /**
     * @var Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Magento\InventorySalesApi\Api\StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var GetProductSalableQtyInterface
     */
    private $productSalable;

    /**
     * @param ShipmentStatus $shipmentStatusModel
     * @param OrderStatus $orderStatusModel
     * @param Action $action
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param GetProductSalableQtyInterface $productSalable
     */
    public function __construct(
        ShipmentStatus $shipmentStatusModel,
        OrderStatus $orderStatusModel,
        Action $action,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        GetProductSalableQtyInterface $productSalable
    ) {
        $this->shipmentStatusModel = $shipmentStatusModel;
        $this->orderStatusModel = $orderStatusModel;
        $this->action = $action;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->productSalable = $productSalable;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Log_Exception
     */
    public function execute(Observer $observer)
    {
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();
        if ($shipment->getOrigData('entity_id')) {
            return;
        }
        $statusUpdate = ShipmentStatus::STATUS_PROCESSING;

        $order = $shipment->getOrder();
        $this->shipmentStatusModel->updateStatus($shipment, $statusUpdate);
        $this->orderStatusModel->setOrderStatus($order);

        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/shipment_after_save_after.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $storeId = $order->getStoreId();
        $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();
        $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
        $stockId    = $this->stockResolver->execute(
            SalesChannelInterface::TYPE_WEBSITE,
            $websiteCode
        )->getStockId();
        $storesIds = $this->storeManager->getWebsite($websiteId)->getStores();
        // update restock flag when stock is 0
        /*$updateAttributes['restock_notification_flag'] = "2";
        foreach ($order->getAllItems() as $item) {
            if ($item->getProductType() == 'simple') {
                $productId = $item->getProductId();
                $sku = $item->getSku();
                $stockQty   = $this->productSalable->execute($sku, $stockId);
                if ($stockQty == 0) {
                    foreach ($storesIds as $storeId) {
                        $storeId = $storeId->getId();
                        if ($storeId > 0) {
                            $logger->info("Restock enabled for $sku after the shipment because product has $stockQty salable qty.");
                            $this->action->updateAttributes([$productId], $updateAttributes, $storeId);
                        }
                    }
                }
            }
        }*/
    }
}
