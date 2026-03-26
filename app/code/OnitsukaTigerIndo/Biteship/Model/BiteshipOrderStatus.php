<?php

namespace OnitsukaTigerIndo\Biteship\Model;

use OnitsukaTiger\Shipment\Model\ShipmentStatus;

class BiteshipOrderStatus implements \OnitsukaTigerIndo\Biteship\Api\BiteshipOrderStatusInterface
{
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $request;
    
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \OnitsukaTiger\Logger\Logger
     */
    protected $logger;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $commonLogger;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Amasty\Rma\Model\Request\Repository
     */
    protected $rmaRepository;

    /**
     * @var \Amasty\Rma\Api\StatusRepositoryInterface
     */
    protected $rmaStatusRepository;

    /**
     * @var SourceMapping
     */
    protected $sourceMapping;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ShipmentStatus
     */
    protected $shipmentStatusModel;

    /**
     * @var OrderStatus
     */
    protected $orderStatusModel;

    /**
     * @var ShipmentCancel
     */
    private $shipmentCancel;

    /**
     * @var Shipment
     */
    protected $shipment;

    /**
     * Constructs a new instance.
     *
     * @param \Magento\Framework\Webapi\Rest\Request $request The request params
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory The json
     * @param \Magento\Sales\Model\OrderFactory $orderFactory The order factory
     * @param \OnitsukaTiger\Logger\Api\Logger $logger The logger
     * @param \Psr\Log\LoggerInterface $commonLogger The common logger
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository The shipment repository
     * @param \Magento\Framework\Event\ManagerInterface $eventManager The event manager
     * @param \Amasty\Rma\Model\Request\Repository $rmaRepository The rma repository
     * @param \Amasty\Rma\Api\StatusRepositoryInterface $rmaStatusRepository The rma status repository
     * @param \OnitsukaTiger\NetSuite\Model\SourceMapping $sourceMapping The source mapping
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder The search criteria builder
     * @param \OnitsukaTiger\Shipment\Model\ShipmentStatus $shipmentStatusModel The shipment status model
     * @param \OnitsukaTigerIndo\Biteship\Model\OrderStatus $orderStatusModel The order status model
     * @param \OnitsukaTiger\CancelShipment\Model\Shipment\Cancel $shipmentCancel The shipment cancel
     * @param \Magento\Sales\Model\Order\Shipment $shipment The shipment
     */
    public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \OnitsukaTiger\Logger\Api\Logger $logger,
        \Psr\Log\LoggerInterface $commonLogger,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Amasty\Rma\Model\Request\Repository $rmaRepository,
        \Amasty\Rma\Api\StatusRepositoryInterface $rmaStatusRepository,
        \OnitsukaTiger\NetSuite\Model\SourceMapping $sourceMapping,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        ShipmentStatus $shipmentStatusModel,
        \OnitsukaTigerIndo\Biteship\Model\OrderStatus $orderStatusModel,
        \OnitsukaTiger\CancelShipment\Model\Shipment\Cancel $shipmentCancel,
        \Magento\Sales\Model\Order\Shipment $shipment
    ) {
        $this->request = $request;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->orderFactory = $orderFactory;
        $this->logger = $logger;
        $this->commonLogger = $commonLogger;
        $this->shipmentRepository = $shipmentRepository;
        $this->eventManager = $eventManager;
        $this->rmaRepository = $rmaRepository;
        $this->rmaStatusRepository = $rmaStatusRepository;
        $this->sourceMapping = $sourceMapping;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentStatusModel = $shipmentStatusModel;
        $this->orderStatusModel = $orderStatusModel;
        $this->shipmentCancel = $shipmentCancel;
        $this->shipment = $shipment;
    }

    /**
     * Status update from biteship to magento.
     *
     * @return \OnitsukaTigerIndo\Biteship\Api\Response\ResponseInterface
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function statusUpdate()
    {

        $biteshipOrderId = $this->request->getParam('order_id');
        $status = $this->request->getParam('status');
        $courierWaybillId = $this->request->getParam('courier_waybill_id');
        
        $this->logger->info(sprintf('----- order-shipment status update() start ----- id [%s]', $biteshipOrderId));

        $order = $this->orderFactory->create()->load($biteshipOrderId, 'biteship_order_id');

        if (isset($courierWaybillId) && !empty($courierWaybillId)) {
            $order->setData('courier_waybill_id', $courierWaybillId)->save();
        }

        foreach ($order->getShipmentsCollection() as $key => $shipment) {
            $this->shipmentStatusModel->updateStatus($shipment, $status);
            $this->orderStatusModel->setOrderStatus($order);
            if ($status == 'shipped') {
                $this->eventManager->dispatch(self::EVENT_UPDATE_ORDER_STATUS_SHIPPED, ['shipment' => $shipment]);
            } elseif ($status == 'delivered') {
                $this->eventManager->dispatch(self::EVENT_UPDATE_ORDER_STATUS_DELIVERED, ['shipment' => $shipment]);
            }
        }

        $ret = new \OnitsukaTigerIndo\Biteship\Model\Response\Response(true);
        $this->logger->info('----- order-shipment status update() end ----- return : ' . $ret->toString());
        return $ret;
    }

    /**
     * API "PrePacked" notification from Biteship
     *
     * @param string $id
     * @return \OnitsukaTigerIndo\Biteship\Api\Response\ResponseInterface
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function statusPrepacked($id)
    {
        $this->logger->info(sprintf('----- statusPrepacked() start ----- id [%s]', $id));
        $this->setOrderShipmentStatus(
            $id,
            ShipmentStatus::STATUS_PREPACKED,
            ShipmentStatus::STATUS_PROCESSING,
            ShipmentStatus::STATUS_PREPACKED
        );
        $ret = new \OnitsukaTigerIndo\Biteship\Model\Response\Response(true);
        $this->logger->info('----- orderPrePacked() end ----- return : ' . $ret->toString());
        return $ret;
    }

    /**
     * API "Packed" notification from Biteship
     *
     * @param string $id
     * @return \OnitsukaTigerIndo\Biteship\Api\Response\ResponseInterface
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function statusPacked($id)
    {
        $this->logger->info(sprintf('----- statusPacked() start ----- id [%s]', $id));
        $this->setOrderShipmentStatus(
            $id,
            ShipmentStatus::STATUS_PACKED,
            ShipmentStatus::STATUS_PREPACKED,
            ShipmentStatus::STATUS_PACKED
        );
        $ret = new \OnitsukaTigerIndo\Biteship\Model\Response\Response(true);
        $this->logger->info('----- orderPacked() end ----- return : ' . $ret->toString());
        return $ret;
    }

    /**
     * API "Shipped" notification from NetSuite
     *
     * @param string $id
     * @return \OnitsukaTigerIndo\Biteship\Api\Response\ResponseInterface
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function statusShipped($id)
    {
        $this->logger->info('----- statusShipped() start ----- id : ' . $id);
        $this->setOrderShipmentStatus(
            $id,
            ShipmentStatus::STATUS_SHIPPED,
            ShipmentStatus::STATUS_PACKED,
            ShipmentStatus::STATUS_SHIPPED
        );
        $ret = new \OnitsukaTigerIndo\Biteship\Model\Response\Response(true);
        $this->logger->info('----- orderShipped() end ----- return : ' . $ret->toString());
        return $ret;
    }

    /**
     * API "Shipped" notification from Biteship
     *
     * @param string $id
     * @return \OnitsukaTigerIndo\Biteship\Api\Response\ResponseInterface
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function statusDelivered($id)
    {
        $this->logger->info('----- statusDelivered() start ----- id : ' . $id);
        $this->setOrderShipmentStatus(
            $id,
            ShipmentStatus::STATUS_DELIVERED,
            ShipmentStatus::STATUS_SHIPPED
        );
        $ret = new \OnitsukaTigerIndo\Biteship\Model\Response\Response(true);
        $this->logger->info('----- orderDelivered() end ----- return : ' . $ret->toString());
        return $ret;
    }

    /**
     * API "Cancel" from Biteship
     *
     * @param string $id
     * @return \OnitsukaTigerIndo\Biteship\Api\Response\ResponseInterface
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function statusCancel($id)
    {
        $this->logger->info('----- statusCancel() start ----- id : ' . $id);
        $this->setOrderShipmentStatus(
            $id,
            ShipmentStatus::STATUS_DELIVERY_FAILED,
            ShipmentStatus::STATUS_PACKED,
            ShipmentStatus::STATUS_SHIPPED
        );
        $ret = new \OnitsukaTigerIndo\Biteship\Model\Response\Response(true);
        $this->logger->info('----- orderCancel() end ----- return : ' . $ret->toString());
        return $ret;
    }

    /**
     * Sets the order shipment status.
     *
     * @param      <type>  $id               The new value
     * @param      <type>  $newStatus        The new status
     * @param      <type>  $existingStatus1  The existing status 1
     * @param      <type>  $existingStatus2  The existing status 2
     */
    private function setOrderShipmentStatus($id, $newStatus, $existingStatus1 = null, $existingStatus2 = null)
    {
        $shipmentId = [];
        $order = $this->orderFactory->create()->load($id, 'biteship_order_id');
        foreach ($order->getShipmentsCollection() as $key => $shipment) {
            $this->validateShipment($shipment, [$existingStatus1, $existingStatus2]);
            $this->shipmentStatusModel->updateStatus($shipment, $newStatus);
            $this->orderStatusModel->setOrderStatus($order);
            if ($newStatus == ShipmentStatus::STATUS_SHIPPED) {
                $this->eventManager->dispatch(self::EVENT_UPDATE_ORDER_STATUS_SHIPPED, ['shipment' => $shipment]);
            } elseif ($newStatus == ShipmentStatus::STATUS_DELIVERED) {
                $this->eventManager->dispatch(self::EVENT_UPDATE_ORDER_STATUS_DELIVERED, ['shipment' => $shipment]);
            } elseif ($newStatus == ShipmentStatus::STATUS_DELIVERY_FAILED) {
                $this->shipmentCancel->execute($shipment);
                /*$this->eventManager->dispatch(
                    \OnitsukaTiger\CancelShipment\Model\Shipment\Cancel::AFTER_CANCEL_SHIPMENT,
                    ['shipment' => $shipment]
                );*/
            }
        }
    }

    /**
     * Shipment validation
     *
     * @param      <type>  $shipment  The shipment
     * @param      array   $status    The status
     */
    private function validateShipment($shipment, array $status)
    {
        $shipmentData = $this->shipmentRepository->get($shipment->getId());
        $ext = $shipmentData->getExtensionAttributes();
        if (!in_array($ext->getStatus(), $status)) {
            $this->throwWebApiException(sprintf(
                'shipment id [%s] is not status[%s]',
                $shipment->getIncrementId(),
                (null !== $status[1]) ? implode(', ', $status) : $status[0]
            ), 400);
        }
    }

    /**
     * Api Throw exception
     *
     * @param      <type>  $msg     The message
     * @param      <type>  $status  The status
     */
    public function throwWebApiException($msg, $status)
    {
        $exception = new \Magento\Framework\Webapi\Exception(__($msg), $status);
        $this->commonLogger->critical($exception);
        throw $exception;
    }
}
