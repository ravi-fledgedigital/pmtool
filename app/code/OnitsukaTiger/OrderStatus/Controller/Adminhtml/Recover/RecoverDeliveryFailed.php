<?php

namespace OnitsukaTiger\OrderStatus\Controller\Adminhtml\Recover;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use OnitsukaTiger\Rma\Helper\NotDelivered;

class RecoverDeliveryFailed extends Action
{
    const ADMIN_RESOURCE = 'Magento_Sales::ship_delivery_failed';

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
     * @var NotDelivered
     */
    protected NotDelivered $notDelivered;

    /**
     * @param Context $context
     * @param OrderRepository $orderRepository
     * @param OrderStatus $orderStatusModel
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentStatus $shipmentStatus
     * @param NotDelivered $notDelivered
     */
    public function __construct(
        Context         $context,
        OrderRepository $orderRepository,
        OrderStatus $orderStatusModel,
        ShipmentRepositoryInterface $shipmentRepository,
        ShipmentStatus $shipmentStatus,
        NotDelivered $notDelivered
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->orderStatusModel = $orderStatusModel;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentStatus = $shipmentStatus;
        $this->notDelivered = $notDelivered;
    }

    public function execute()
    {
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($shipmentId) {
            $viewUrl = $resultRedirect->setPath('adminhtml/order_shipment/view', ['shipment_id' => $shipmentId]);
            try {
                $shipment = $this->shipmentRepository->get($shipmentId);
                /** @var Order $order */
                $order = $this->orderRepository->get($shipment->getOrderId());

                if (!$trackList = $shipment->getTracks()) {
                    $this->messageManager->addWarningMessage(__('Shipment does not exist any tracking number.'));
                    return $viewUrl;
                }

                $track = end($trackList);
                $trackingNumber = $track->getTrackNumber();

                $this->shipmentStatus->updateStatus($shipment, ShipmentStatus::STATUS_DELIVERY_FAILED);
                $this->orderStatusModel->setComment('Recover Delivered Failed By Admin')->setOrderStatus($order);
                $this->notDelivered->makeNotDeliveredRequest($shipment, $trackingNumber);

                $this->messageManager->addSuccessMessage(__('Recover Delivered Failed Successfully.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
            return $viewUrl;
        }

        return $resultRedirect->setPath('*/*/');
    }
}
