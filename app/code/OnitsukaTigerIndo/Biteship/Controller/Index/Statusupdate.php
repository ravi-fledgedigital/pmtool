<?php

namespace OnitsukaTigerIndo\Biteship\Controller\Index;

use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use Magento\Sales\Api\ShipmentRepositoryInterface;

class Statusupdate extends ApiController
{
    /**
     * Event : update order status to shipped
     */
    public const EVENT_UPDATE_ORDER_STATUS_SHIPPED = 'biteship_update_order_status_shipped';

    /**
     * Event : update order status to delivered
     */
    public const EVENT_UPDATE_ORDER_STATUS_DELIVERED = 'biteship_update_order_status_delivered';

    /**
     * @var \Amasty\Rma\Api\RequestRepositoryInterface
     */
    protected $requestRepository;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \OnitsukaTiger\Shipment\Model\ShipmentStatus
     */
    protected $shipmentStatusModel;

    /**
     * @var \OnitsukaTigerIndo\Biteship\Model\OrderStatus
     */
    protected $orderStatusModel;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\App\Action\Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    protected $trackFactory;

    /**
     * @var \Amasty\Rma\Model\ConfigProvider
     */
    protected $configProvider;

    /**
     * @var \Amasty\Rma\Model\Request\Email\EmailRequest
     */
    protected $emailRequest;

    /**
     * @var \Amasty\Rma\Utils\Email
     */
    protected $emailSender;

    /**
     * Serialize data to JSON, unserialize JSON encoded data
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    public $json;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    public $shipmentRepository;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    public $_pageFactory;

    /**
     * Constructs a new instance.
     *
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param \Amasty\Rma\Model\ConfigProvider $configProvider
     * @param \Amasty\Rma\Model\Request\Email\EmailRequest $emailRequest
     * @param \Amasty\Rma\Utils\Email $emailSender
     * @param \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory
     * @param \Amasty\Rma\Api\RequestRepositoryInterface $requestRepository
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param ShipmentStatus $shipmentStatusModel
     * @param \OnitsukaTigerIndo\Biteship\Model\OrderStatus $orderStatusModel
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     */
    public function __construct(
        ShipmentRepositoryInterface $shipmentRepository,
        \Amasty\Rma\Model\ConfigProvider $configProvider,
        \Amasty\Rma\Model\Request\Email\EmailRequest $emailRequest,
        \Amasty\Rma\Utils\Email $emailSender,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        \Amasty\Rma\Api\RequestRepositoryInterface $requestRepository,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        ShipmentStatus $shipmentStatusModel,
        \OnitsukaTigerIndo\Biteship\Model\OrderStatus $orderStatusModel,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Serialize\Serializer\Json $json
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->configProvider = $configProvider;
        $this->emailRequest = $emailRequest;
        $this->emailSender = $emailSender;
        $this->trackFactory = $trackFactory;
        $this->requestRepository = $requestRepository;
        $this->orderFactory = $orderFactory;
        $this->shipmentStatusModel = $shipmentStatusModel;
        $this->orderStatusModel = $orderStatusModel;
        $this->eventManager = $eventManager;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_pageFactory = $pageFactory;
        $this->json = $json;
        return parent::__construct($context);
    }

    /**
     * Update status of order and shipment
     *
     * @return \OnitsukaTigerIndo\Biteship\Api\Response\ResponseInterface
     */
    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/Statusupdate_' . date('d-m-y') . '.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('=========Order status AWB update start=========');
        $logger->info('=========get request from url=========');

        $requestData = $this->getRequest()->getContent();
        $data = $this->json->unserialize($requestData, true);
        if (!empty($data)) {
            $biteshipOrderId = $data['order_id'];
            $status = $data['status'];
            $courierWaybillId = $data['courier_waybill_id'];
            $courierCompany = $data['courier_company'];

            $logger->info("biteshipOrderId = " . $biteshipOrderId);
            $logger->info("status = " . $status);
            $logger->info("courierWaybillId = " . $courierWaybillId);
            $logger->info("courierCompany = " . $courierCompany);

            if (in_array($status, ['placed', 'scheduled', 'confirmed'])) {
                $status = 'processing';
            } elseif ($status == 'allocated') {
                $status = 'prepacked';
            } elseif ($status == 'picking_up') {
                $status = 'packed';
            } elseif ($status == 'picked') {
                $status = 'shipped';
            } elseif (in_array($status, ['delivered', 'finished'])) {
                $status = 'delivered';
            } elseif (in_array($status, ['cancelled', 'disposed', 'returned'])) {
                $status = 'delivery_failed';
            } elseif (in_array($status, ['on_hold', 'detented'])) {
                $status = 'holded';
            } elseif ($status == 'dropping_off') {
                $status = 'out_for_delivery';
            }
            $logger->info("Updated Status = " . $status);

            $logger->info("=========getting order details from biteship_order_id=========");
            $order = $this->orderFactory->create()->load($biteshipOrderId, 'biteship_order_id');

            $trackingData = [
                'carrier_code' => 'custom',
                'title' => $courierCompany,
                'number' => $courierWaybillId,
            ];

            $templateIdentifier = 'amrma_email_user_template_initial';

            foreach ($order->getShipmentsCollection() as $key => $shipment) {
                $shipment = $this->shipmentRepository->get($shipment->getId());
                $logger->info("=========shipment track collection=========");
                if ($shipment->getTracksCollection()->getSize() == 0) {
                    $logger->info("=========shipment does not have tracking collection=========");
                    $track = $this->trackFactory->create()->addData($trackingData);
                    $shipment->addTrack($track)->save();
                    $logger->info("=========tracking information saved in shipment=========");
                }
                $logger->info("=========start updating shipment status=========");
                $this->shipmentStatusModel->updateStatus($shipment, $status);
                $logger->info("=========start updating order status=========");
                $this->orderStatusModel->setOrderStatus($order);
                if ($status == 'shipped') {
                    $logger->info("=========send mail when order gets shipped status=========");
                    $this->eventManager->dispatch(self::EVENT_UPDATE_ORDER_STATUS_SHIPPED, ['shipment' => $shipment]);
                } elseif ($status == 'delivered') {
                    $logger->info("=========send mail when order gets delivered status=========");
                    $this->eventManager->dispatch(self::EVENT_UPDATE_ORDER_STATUS_DELIVERED, ['shipment' => $shipment]);
                } elseif ($status == 'delivery_failed') {
                    $logger->info("=========start creating RMA=========");
                    $request = $this->requestRepository->getEmptyRequestModel();
                    $logger->info("=========get Items array from shipment=========");
                    foreach ($shipment->getItems() as $shipmentItem) {
                        $request->setNote('Delivery failed by Biteship')
                            ->setStatus('4')
                            ->setCustomerId($order->getCustomerId())
                            ->setManagerId('')
                            ->setOrderId($order->getId())
                            ->setStoreId($order->getStoreId())
                            ->setShipmentIncrementId($shipment->getIncrementId())
                            ->setCustomerName(
                                $order->getBillingAddress()->getFirstname()
                                . ' ' . $order->getBillingAddress()->getLastname()
                            );

                        $orderItems = $shipment->getOrder()->getAllItems();
                        $shipmentChildItem = $shipmentItem;
                        foreach ($orderItems as $orderItem) {
                            if ($orderItem->getParentItemId() == $shipmentItem->getOrderItemId()) {
                                $shipmentChildItem = $orderItem;
                                break;
                            }
                        }

                        $requestItem = $this->requestRepository->getEmptyRequestItemModel();
                        $requestItem->setItemStatus(0)
                            ->setOrderItemId($shipmentChildItem->getItemId())
                            ->setConditionId(1) // Unopened
                            ->setReasonId(1) // Wrong Product Delivered
                            ->setResolutionId(2) // Return
                            ->setRequestQty($shipmentItem->getQty())
                            ->setQty($shipmentItem->getQty());

                        $requestItems[] = $requestItem;
                    }
                    $logger->info("=========requestItems Array=========");
                    $request->setRequestItems($requestItems);
                    $this->requestRepository->save($request);
                    $logger->info("=========return created successfully=========");

                    $logger->info("=========Start Email sending to customer=========");
                    $emailRequest = $this->emailRequest->parseRequest($request);
                    $this->emailSender->sendEmail(
                        $emailRequest->getCustomerEmail(),
                        $request->getStoreId(),
                        $templateIdentifier,
                        ['email_request' => $emailRequest],
                        \Magento\Framework\App\Area::AREA_FRONTEND,
                        $this->configProvider->getSender($request->getStoreId())
                    );
                    $logger->info("=========Email Send to customer=========");
                }
            }
        }
        $logger->info("=========successfully updates the order shipment status=========");
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData(['success' => true]);
    }
}
