<?php

namespace OnitsukaTigerKorea\Sales\Controller\Adminhtml\Sales\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpImport;

class Delivered extends Action
{
    const ADMIN_RESOURCE = 'Magento_Sales::sales';

    const COMMENT_HISTORY = 'Delivered By Admin';

    /**
     * @var ShipmentRepositoryInterface
     */
    protected ShipmentRepositoryInterface $shipmentRepository;

    /**
     * @var OrderRepository
     */
    protected OrderRepository $orderRepository;

    /**
     * @var ManagerInterface
     */
    protected ManagerInterface $eventManager;

    /**
     * @var OrderStatus
     */
    protected OrderStatus $orderStatusModel;

    /**
     * @var ShipmentStatus
     */
    protected ShipmentStatus $shipmentStatus;

    /**
     * @param Context $context
     * @param OrderRepository $orderRepository
     * @param ManagerInterface $eventManager
     * @param OrderStatus $orderStatusModel
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentStatus $shipmentStatus
     */
    public function __construct(
        Context                $context,
        OrderRepository        $orderRepository,
        ManagerInterface $eventManager,
        OrderStatus $orderStatusModel,
        ShipmentRepositoryInterface $shipmentRepository,
        ShipmentStatus $shipmentStatus
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->eventManager = $eventManager;
        $this->orderStatusModel = $orderStatusModel;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentStatus = $shipmentStatus;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $orderId = $this->getRequest()->getParam('ord_id');

        if ($orderId) {
            $orderViewUrl = $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
            try {
                /** @var Order $order */
                $order = $this->orderRepository->get($orderId);

                if ($order->hasShipments()) {
                    $shipments = $this->shipmentStatus->getShipmentDataByOrderId($orderId);
                    $shipment = array_values($shipments)[0];
                    $this->shipmentStatus->updateStatus($shipment, OrderStatus::STATUS_DELIVERED);
                }

                $order->setStatus(OrderStatus::STATUS_DELIVERED);
                $order->setState(Order::STATE_COMPLETE);
                $order->addCommentToStatusHistory(self::COMMENT_HISTORY, OrderStatus::STATUS_DELIVERED);
                $this->orderRepository->save($order);

                $this->messageManager->addSuccessMessage(__('Order status updated to Delivered successfully.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
            return $orderViewUrl;
        }

        return $resultRedirect->setPath('*/*/');
    }
}
