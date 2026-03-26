<?php

namespace OnitsukaTiger\OrderStatus\Controller\Adminhtml\Recover;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use Magento\Framework\Event\ManagerInterface;


class RecoverDelivered extends Action
{
    const ADMIN_RESOURCE = 'Magento_Sales::ship_delivered';

    /**
     * @var OrderRepository
     */
    protected OrderRepository $orderRepository;

    /**
     * @var OrderStatus
     */
    protected OrderStatus $orderStatusModel;

    /**
     * @var ShipmentStatus
     */
    protected ShipmentStatus $shipmentStatus;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected ShipmentRepositoryInterface $shipmentRepository;

    /**
     * @param Context $context
     * @param OrderRepository $orderRepository
     * @param OrderStatus $orderStatusModel
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentStatus $shipmentStatus
     */
    public function __construct(
        Context         $context,
        OrderRepository $orderRepository,
        OrderStatus $orderStatusModel,
        ShipmentRepositoryInterface $shipmentRepository,
        ShipmentStatus $shipmentStatus,
        private \OnitsukaTiger\NetSuite\Model\SuiteTalk\UpdateShipmentStatusToNetsuite $updateShipmentStatusToNetsuite,
        private SourceRepositoryInterface $sourceRepository,
        private \OnitsukaTiger\Cegid\Model\UpdateShipmentStatusToCegid  $updateShipmentStatusToCegid,
        private ManagerInterface $eventManager
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->orderStatusModel = $orderStatusModel;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentStatus = $shipmentStatus;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $shipmentId = $this->getRequest()->getParam('shipment_id');

        if ($shipmentId) {
            $viewUrl = $resultRedirect->setPath('adminhtml/order_shipment/view', ['shipment_id' => $shipmentId]);
            try {
                $shipment = $this->shipmentRepository->get($shipmentId);
                /** @var Order $order */
                $order = $this->orderRepository->get($shipment->getOrderId());

                $this->shipmentStatus->updateStatus($shipment, ShipmentStatus::STATUS_DELIVERED);
                $this->orderStatusModel->setComment('Recover Delivered By Admin')->setOrderStatus($order);

                if (in_array($shipment->getStoreId(), [8, 10])) {
                    $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
                    try {
                        $source = $this->sourceRepository->get($sourceCode);
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        $this->messageManager->addErrorMessage($e->getMessage());
                    }

                    if ($source && $source->getIsShippingFromStore()) {
                        $this->updateShipmentStatusToCegid->execute($shipment, ShipmentStatus::STATUS_DELIVERED);
                    } else {
                        $this->updateShipmentStatusToNetsuite->execute($shipment, ShipmentStatus::STATUS_DELIVERED);
                    }
                }
                $this->eventManager->dispatch("netsuite_update_order_status_delivered", ['shipment' => $shipment]);
                $this->messageManager->addSuccessMessage(__('Recover Delivered Successfully.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
            return $viewUrl;
        }

        return $resultRedirect->setPath('*/*/');
    }
}
