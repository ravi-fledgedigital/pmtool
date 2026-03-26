<?php

namespace OnitsukaTiger\Cegid\Model;

use Magento\Sales\Api\ShipmentItemRepositoryInterface;
use OnitsukaTiger\Shipment\Model\ResourceModel\ShipmentAttributes as ResourceModel;
use OnitsukaTiger\Shipment\Model\ResourceModel\ShipmentAttributes\CollectionFactory;
use OnitsukaTiger\Shipment\Model\ShipmentAttributesFactory as ModelFactory;
use OnitsukaTiger\CancelShipment\Model\Shipment\Cancel as ShipmentCancel;
use OnitsukaTiger\Cegid\Service\ApiAction;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use Magento\Framework\Event\ManagerInterface;
use OnitsukaTiger\NetSuite\Api\NetSuiteInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use OnitsukaTiger\Shipment\Model\ShipmentAttributes;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\InventorySourceDeductionApi\Model\ItemToDeductInterfaceFactory;
use OnitsukaTiger\CancelShipment\Model\SourceDeductionRequestFromShipmentFactory;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionServiceInterface;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;
use Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface;
use OnitsukaTiger\Cegid\Logger\Logger;

class ShipmentUpdate
{
    public const STATUS_REJECTED        = 'rejected';
    public const STATUS_PACKED          = 'packed';
    public const STATUS_SHIPPED         = 'shipped';
    public const ROUTES_UPDATE_SHIPMENT = '/rest/V1/shipment';
    public const CEGID_SHIPMENT_NO_SYNC = 0;
    public const CEGID_SHIPMENT_SYNC    = 1;

    /**
     * @var ResourceModel
     */
    protected $resourceModel;

    /**
     * @var ModelFactory
     */
    protected $modelFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ShipmentItemRepositoryInterface
     */
    protected $shipmentItemRepository;

    /**
     * @var ShipmentCancel
     */
    protected $shipmentCancel;

    /**
     * @var ApiAction
     */
    protected $apiAction;

    /**
     * @var OrderStatus
     */
    protected $orderStatusModel;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var ItemToDeductInterfaceFactory
     */
    protected $itemToDeduct;

    /**
     * @var SourceDeductionRequestFromShipmentFactory
     */
    protected $sourceDeductionRequestFromShipmentFactory;

    /**
     * @var SourceDeductionServiceInterface
     */
    protected $sourceDeductionService;

    /**
     * @var ItemToSellInterfaceFactory
     */
    protected $itemsToSellFactory;

    /**
     * @var PlaceReservationsForSalesEventInterface
     */
    protected $placeReservationsForSalesEvent;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param ModelFactory $modelFactory
     * @param ResourceModel $resourceModel
     * @param CollectionFactory $collectionFactory
     * @param ShipmentItemRepositoryInterface $shipmentItemRepository
     * @param ShipmentCancel $shipmentCancel
     * @param ApiAction $apiAction
     * @param OrderStatus $orderStatusModel
     * @param ManagerInterface $eventManager
     * @param OrderRepositoryInterface $orderRepository
     * @param ItemToDeductInterfaceFactory $itemToDeduct
     * @param SourceDeductionRequestFromShipmentFactory $sourceDeductionRequestFromShipmentFactory
     * @param SourceDeductionServiceInterface $sourceDeductionService
     * @param ItemToSellInterfaceFactory $itemsToSellFactory
     * @param PlaceReservationsForSalesEventInterface $placeReservationsForSalesEvent
     * @param Logger $logger
     */
    public function __construct(
        ModelFactory $modelFactory,
        ResourceModel $resourceModel,
        CollectionFactory $collectionFactory,
        ShipmentItemRepositoryInterface $shipmentItemRepository,
        ShipmentCancel $shipmentCancel,
        ApiAction $apiAction,
        OrderStatus $orderStatusModel,
        ManagerInterface $eventManager,
        OrderRepositoryInterface $orderRepository,
        ItemToDeductInterfaceFactory $itemToDeduct,
        SourceDeductionRequestFromShipmentFactory $sourceDeductionRequestFromShipmentFactory,
        SourceDeductionServiceInterface $sourceDeductionService,
        ItemToSellInterfaceFactory $itemsToSellFactory,
        PlaceReservationsForSalesEventInterface $placeReservationsForSalesEvent,
        Logger $logger
    ) {
        $this->resourceModel                             = $resourceModel;
        $this->modelFactory                              = $modelFactory;
        $this->collectionFactory                         = $collectionFactory;
        $this->shipmentItemRepository                    = $shipmentItemRepository;
        $this->shipmentCancel                            = $shipmentCancel;
        $this->apiAction                                 = $apiAction;
        $this->orderStatusModel                          = $orderStatusModel;
        $this->eventManager                              = $eventManager;
        $this->orderRepository                           = $orderRepository;
        $this->itemToDeduct                              = $itemToDeduct;
        $this->sourceDeductionRequestFromShipmentFactory = $sourceDeductionRequestFromShipmentFactory;
        $this->sourceDeductionService                    = $sourceDeductionService;
        $this->itemsToSellFactory                        = $itemsToSellFactory;
        $this->placeReservationsForSalesEvent            = $placeReservationsForSalesEvent;
        $this->logger                                    = $logger;
    }

    /**
     * DeleteShipment
     *
     * @param object $shipment
     * @param array $params
     * @return void
     * @throws CouldNotSaveException
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function deleteShipment($shipment, $params)
    {
        $this->isAllItemsShipment($params, $shipment->getItems());
        try {
            $this->shipmentCancel->execute($shipment);
            $this->eventManager->dispatch(ShipmentCancel::AFTER_CANCEL_SHIPMENT, ['shipment' => $shipment]);

            $itemsUpdateInventory = [];
            foreach ($shipment->getItems() as $item) {
                $itemsUpdateInventory[] = $this->itemToDeduct->create([
                    'sku' => $item->getSku(),
                    'qty' => -$item->getQty()
                ]);
            }
            $this->updateInventoryReservation($shipment, $itemsUpdateInventory);

            $message = __('API Successfully delete shipment ID%1.', $shipment->getId());
            $this->addCommenthistoryOrder($shipment, $message);
            $this->throwWebApiException($message, 200);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->warning(__($e->getMessage()));
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }
    }

    /**
     * Update Shipment Shipped
     *
     * @param object $shipment
     * @param mixed $params
     * @return void
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function updateShipmentShipped($shipment, $params)
    {
        $this->isAllItemsShipment($params, $shipment->getItems());
        try {
            $this->updateStatusShipment($shipment->getEntityId());
            $message = __('API Successfully Update Shipment Shipped', $shipment->getEntityId());
            $this->addCommenthistoryOrder($shipment, $message);
            $this->eventManager->dispatch(
                NetSuiteInterface::EVENT_UPDATE_ORDER_STATUS_SHIPPED,
                ['shipment' => $shipment]
            );
            $this->logger->info('----- orderPacked() end -----');
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->warning(__($e->getMessage()));
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * UpdateShipmentPacked
     *
     * @param object $shipmentModel
     * @param mixed $params
     * @return int|mixed
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function updateShipmentPacked($shipmentModel, $params)
    {
        try {
            $totalQtyChange       = 0;
            $itemsUpdateInventory = [];
            if (isset($params['items'])) {
                $shipmentItems = $shipmentModel->getItems();
                foreach ($params['items'] as $item) {
                    if (!isset($shipmentItems[$item->getEntityId()])) {
                        $this->throwWebApiException('Item does not exist.', 400);
                    }
                    if ($item->getQty() == 0) {
                        continue;
                    }

                    if ($item->getQty() != $shipmentItems[$item->getEntityId()]->getQty()) {
                        $qtyChangeItem = $shipmentItems[$item->getEntityId()]->getQty() - $item->getQty();
                        $this->setQtyShippedOrderItem($shipmentItems[$item->getEntityId()], $item->getQty());
                        $totalQtyChange         += $qtyChangeItem;
                        $itemsUpdateInventory[] = $this->itemToDeduct->create([
                            'sku' => $item->getSku(),
                            'qty' => -$qtyChangeItem
                        ]);
                    }
                    unset($shipmentItems[$item->getEntityId()]);
                }

                //delete shipment item
                foreach ($shipmentItems as $entityId => $remainingItem) {
                    $this->setQtyShippedOrderItem($remainingItem, 0);
                    $totalQtyChange += $remainingItem->getQty();
                    $this->deleteShipmentItem($entityId);
                    $itemsUpdateInventory[] = $this->itemToDeduct->create([
                        'sku' => $remainingItem->getSku(),
                        'qty' => -$remainingItem->getQty()
                    ]);
                }
            }
            if (count($itemsUpdateInventory) > 0) {
                $this->updateInventoryReservation($shipmentModel, $itemsUpdateInventory);
            }
            $this->updateStatusShipment($shipmentModel->getEntityId(), ShipmentStatus::STATUS_PREPACKED);
            $message = __('API Successfully Update Shipment PrePacked', $shipmentModel->getEntityId());
            $this->addCommenthistoryOrder($shipmentModel, $message);

            $this->logger->info(sprintf('dispatch event : external id[%s] to packed by store shipping',
                $shipmentModel->getIncrementId()));
            $shipmentModel->getExtensionAttributes()->setStatus(ShipmentStatus::STATUS_PREPACKED);
            $this->eventManager->dispatch(
                'netsuite_update_order_status_prepacked',
                ['shipment' => $shipmentModel]
            );

            $this->updateStatusShipment($shipmentModel->getEntityId());
            $message = __('API Successfully Update Shipment Packed', $shipmentModel->getEntityId());
            $this->addCommenthistoryOrder($shipmentModel, $message);
            $this->logger->info('----- orderPacked() end -----');
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->warning(__($e->getMessage()));
            throw new \Exception($e->getMessage());
        }

        return $totalQtyChange;
    }

    /**
     * Applying qty to order item
     *
     * @param mixed $shipmentItem
     * @param mixed $qtyUpdate
     * @return void
     */
    public function setQtyShippedOrderItem($shipmentItem, $qtyUpdate): void
    {
        $orderItem = $shipmentItem->getOrderItem();
        $orderItem->setQtyShipped($qtyUpdate)->save();
    }

    /**
     * DeleteShipmentItem
     *
     * @param int $shipmentItemId
     * @return false|\Magento\Sales\Api\Data\ShipmentItemInterface
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteShipmentItem(int $shipmentItemId)
    {
        $shipmentItemData = false;
        try {
            $shipmentItemData = $this->shipmentItemRepository->get($shipmentItemId);
            if ($shipmentItemData) {
                $this->shipmentItemRepository->delete($shipmentItemData);
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->warning($e->getMessage());
        }

        return $shipmentItemData;
    }

    /**
     * Validate Shipment Status
     *
     * @param object $shipment
     * @param string $statusUpdate
     * @return void
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function validateShipment($shipment, $statusUpdate)
    {
        $shipmentStatus = $shipment->getExtensionAttributes()->getStatus();
        $this->logger->info('shipment status: ' . $shipmentStatus);
        $status         = [];
        if ($statusUpdate == self::STATUS_REJECTED) {
            $this->logger->info('Shipment status is STATUS_PROCESSING');
            $status = [ShipmentStatus::STATUS_PROCESSING];
        } elseif ($statusUpdate == self::STATUS_PACKED) {
            $this->logger->info('Shipment status is STATUS_PROCESSING');
            $status = [ShipmentStatus::STATUS_PROCESSING, ShipmentStatus::STATUS_PREPACKED];
        } elseif ($statusUpdate == self::STATUS_SHIPPED) {
            $this->logger->info('Shipment status is STATUS_PACKED');
            $status = [ShipmentStatus::STATUS_PACKED];
        }

        if (count($status) > 0 && !in_array($shipmentStatus, $status)) {
            $this->throwWebApiException(__('shipment id [%1] is not status[%2]', $shipment->getIncrementId(),
                implode(', ', $status)), 400);
        }
    }

    /**
     * Check and decode json string
     *
     * @param string $string
     * @return mixed
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function jsonDecode($string)
    {
        $data = json_decode($string);
        if (!$data) {
            $this->throwWebApiException('invalid json format', 400);
        }
        if (!property_exists($data, 'entity')) {
            $this->throwWebApiException('invalid json format', 400);
        }
        if (!property_exists($data->entity, 'entity_id')) {
            $this->throwWebApiException('invalid json format', 400);
        }
        if (!property_exists($data->entity, 'extension_attributes')) {
            $this->throwWebApiException('invalid json format', 400);
        }
        if (!property_exists($data->entity, 'order_id')) {
            $this->throwWebApiException('invalid json format', 400);
        }
        if (!property_exists($data->entity, 'items')) {
            $this->throwWebApiException('invalid json format', 400);
        }

        return $data;
    }

    /**
     * Throw Web API exception and add it to log
     *
     * @param mixed $msg
     * @param mixed $status
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function throwWebApiException($msg, $status)
    {
        $exception = new \Magento\Framework\Webapi\Exception(__($msg), $status);
        throw $exception;
    }

    /**
     * Update Inventory Reservation
     *
     * @param object $shipment
     * @param array $items
     * @return void
     */
    public function updateInventoryReservation($shipment, $items)
    {
        $sourceDeductionRequest = $this->sourceDeductionRequestFromShipmentFactory->execute(
            $shipment,
            $shipment->getExtensionAttributes()->getSourceCode(),
            $items
        );
        $this->sourceDeductionService->execute($sourceDeductionRequest);
        $this->placeCompensatingReservation($sourceDeductionRequest);
    }

    /**
     * Update Status Shipment
     *
     * @param int $id
     * @param mixed $status
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function updateStatusShipment($id, $status = null)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('shipment_id', $id);

        /** @var ShipmentAttributes $firstItem */
        $shipmentAttributesModel = $collection->getFirstItem();
        if (!$shipmentAttributesModel) {
            $shipmentAttributesModel = $this->modelFactory->create();
        }
        $shipmentAttributesModel->setShipmentId((int)$id);
        $shipmentAttributesModel->setStatus($status ?? $this->apiAction->getShipmentStatusOld());
        $this->resourceModel->save($shipmentAttributesModel);
    }

    /**
     * Place compensating reservation for inventory_reservation table after source deduction
     *
     * @param SourceDeductionRequestInterface $sourceDeductionRequest
     */
    public function placeCompensatingReservation(SourceDeductionRequestInterface $sourceDeductionRequest): void
    {
        $items = [];
        foreach ($sourceDeductionRequest->getItems() as $item) {
            $items[] = $this->itemsToSellFactory->create([
                'sku' => $item->getSku(),
                'qty' => $item->getQty()
            ]);
        }
        $this->placeReservationsForSalesEvent->execute(
            $items,
            $sourceDeductionRequest->getSalesChannel(),
            $sourceDeductionRequest->getSalesEvent()
        );
    }

    /**
     * Check is All Items Shipment
     *
     * @param mixed $params
     * @param mixed $shipmentItems
     * @return void
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function isAllItemsShipment($params, $shipmentItems)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/deleteShipment.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Delete Shipment Start============================');
        foreach ($params['items'] as $item) {
            $logger->info("Shipment Item ID: " . $item->getEntityId());
            $logger->info("Shipment Data: " . print_r($shipmentItems, true));
            if (!isset($shipmentItems[$item->getEntityId()])) {
                $this->throwWebApiException('Item does not exist.', 400);
            }
            $logger->info("Shipment Item Qty: " . $item->getQty());
            if ($item->getQty() != $shipmentItems[$item->getEntityId()]->getQty()) {
                $this->throwWebApiException('Qty quantity in item is incorrect.', 400);
            }
            unset($shipmentItems[$item->getEntityId()]);
        }
        if (count($shipmentItems) > 0) {
            $this->throwWebApiException('Missing shipment item.', 400);
        }
        $logger->info('==========================Delete Shipment End============================');
    }

    /**
     * Add Comment History Order
     *
     * @param object $shipment
     * @param mixed $message
     * @return void
     */
    public function addCommenthistoryOrder($shipment, $message)
    {
        $order = $shipment->getOrder();
        $this->orderStatusModel->setOrderStatus($order);
        $orderRepo = $this->orderRepository->get($order->getId());
        $orderRepo->addCommentToStatusHistory($message);
        $this->orderRepository->save($orderRepo);
    }
}
