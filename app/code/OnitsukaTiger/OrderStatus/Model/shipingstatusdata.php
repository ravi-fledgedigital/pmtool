<?php

namespace OnitsukaTiger\OrderStatus\Model;

use Exception;
use Magento\Framework\App\Request\Http;
use Magento\Sales\Model\Order;
use OnitsukaTiger\KerryConNo\Model\ResourceModel\ShippingTrackHistory\Collection as ShippingTrackHistoryCollection;
use OnitsukaTiger\KerryConNo\Model\TrackingNumber;
use OnitsukaTiger\OrderStatus\Model\Response\KerryResponse;
use OnitsukaTiger\OrderStatus\Model\Response\KerryResponseStatus;
use OnitsukaTiger\OrderStatus\Model\Response\KerryResponseStatusDetail;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;

class shipingstatusdata extends \Clickend\Kerry\Model\shipingstatusdata
{
    /**
     * Event : update order status to shipped
     */
    const EVENT_SEND_EMAIL_DELIVERED = 'netsuite_update_order_status_delivered';

    private $request;
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var TrackingNumber
     */
    protected $trackingNumber;
    /**
     * @var \OnitsukaTiger\KerryConNo\Model\ShippingTrackHistoryFactory
     */
    protected $shippingTrackHistoryFactory;
    /**
     * @var \OnitsukaTiger\KerryConNo\Model\ResourceModel\ShippingTrackHistory
     */
    protected $shippingTrackHistoryResource;
    /**
     * @var \OnitsukaTiger\Rma\Helper\NotDelivered
     */
    protected $notDelivered;
    /**
     * @var \OnitsukaTiger\Logger\Kerry\Logger
     */
    protected $logger;

    /**
     * @var ShipmentStatus
     */
    protected $shipmentStatusModel;

    /**
     * @var OrderStatus
     */
    protected $orderStatusModel;

    /**
     * @var ShippingTrackHistoryCollection
     */
    private $shippingTrackHistoryCollection;

    public $currentShipment;

    public function __construct(
        Http $request,
        \Clickend\Kerry\Helper\Data $dataHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $shipmentTrackFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        TrackingNumber $trackingNumber,
        \OnitsukaTiger\KerryConNo\Model\ShippingTrackHistoryFactory $shippingTrackHistoryFactory,
        \OnitsukaTiger\KerryConNo\Model\ResourceModel\ShippingTrackHistory $shippingTrackHistoryResource,
        \OnitsukaTiger\Rma\Helper\NotDelivered $notDelivered,
        ShipmentStatus $shipmentStatusModel,
        OrderStatus $orderStatusModel,
        \OnitsukaTiger\Logger\Kerry\Logger $logger,
        ShippingTrackHistoryCollection $shippingTrackHistoryCollection
    )
    {
        $this->request = $request;
        $this->eventManager = $eventManager;
        $this->trackingNumber = $trackingNumber;
        $this->shippingTrackHistoryFactory = $shippingTrackHistoryFactory;
        $this->shippingTrackHistoryResource = $shippingTrackHistoryResource;
        $this->notDelivered = $notDelivered;
        $this->shipmentStatusModel = $shipmentStatusModel;
        $this->orderStatusModel = $orderStatusModel;
        $this->logger = $logger;
        $this->shippingTrackHistoryCollection = $shippingTrackHistoryCollection;
        parent::__construct(
            $request,
            $dataHelper,
            $resourceConnection,
            $order,
            $orderRepository,
            $shipmentTrackFactory,
            $transactionFactory,
            $shipmentFactory
        );
    }

    public function shipingstatusdata()
    {
        $this->logger->info("================ Shipping Update Status =================");
        $data = json_decode($this->request->getContent(), true);
        $this->logger->info(json_encode($data));

        if (isset($data['req'])) {
            $err = false;
            if (array_key_exists('status', $data['req'])) {
                $data['req'] = [$data['req']];
            }
            foreach ($data['req'] as $item) {
                try {
                    $con_no = $item['status']['con_no'];
                    $status_code = $item['status']['status_code'];
                    $status_desc = $item['status']['status_desc'];
                    $status_date = $item['status']['status_date'];
                    $update_date = $item['status']['update_date'];

                    $location = '';
                    if ($item['status']['location']) {
                        $location = "( " . $item['status']['location'] . " )";
                    }

                    /* @var $order Order */
                    $order = $this->trackingNumber->getOrderFromTrackingNumber($con_no);
                    if (!$order) {
                        $err = true;
                        $this->logger->err("Wrong con_no : " . $con_no);
                        continue;
                    }

                    // Prevent Kerry send same message several times
                    $cnt = $this->shippingTrackHistoryCollection->getExistsByConNoAndServiceCode($con_no, $status_code);
                    if ($cnt > 0) {
                        $err = true;
                        $this->logger->err("[Wrong con_no : $con_no] Kerry send same message several times");
                        continue;
                    }

                    $status = $status_desc . " " . $location;

                    $check_con = $this->resourceConnection->getConnection()->fetchAll("select count(con_no) as num from kerry_shipping_track where con_no='" . $con_no . "';");
                    $this->logger->info("================ Query Found [" . $check_con[0]['num'] . "] =================");

                    if ($check_con[0]['num'] < 1) {
                        $err = true;
                        $this->logger->err("[Wrong con_no : $con_no] Query Insert shipping status Unsuccessfully");
                        continue;
                    }

                    if ($status_code == "POD") {
                        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
                        $shipment = $this->trackingNumber->getShipmentFromTrackingNumber($con_no);
                        if ($shipment) {
                            $this->shipmentStatusModel->updateStatus($shipment, ShipmentStatus::STATUS_DELIVERED);
                            $this->orderStatusModel->setOrderStatus($order);
                        }

                        /** @var \OnitsukaTiger\KerryConNo\Model\ShippingTrackHistory $model */
                        $model = $this->shippingTrackHistoryFactory->create();
                        $model->setConNo($con_no);
                        $model->setOrderId($shipment->getIncrementId());
                        $model->setStatus('Delivered');
                        $model->setDescription($status);
                        $model->setServiceCode($status_code);
                        $model->setCreateTime($status_date);
                        $model->setUpdateTime($update_date);
                        $this->shippingTrackHistoryResource->save($model);

                        // dispatch event
                        $this->eventManager->dispatch(self::EVENT_SEND_EMAIL_DELIVERED, ['shipment' => $shipment]);

                        $kerryResponseStatusDetail = new KerryResponseStatusDetail('000', 'Successfully');

                        $this->logger->info("Query Insert shipping status Successfully");
                        $this->setCurrentShipment($shipment)->addstatus($order->getId(), 'Delivered', $status, $status);
                    } elseif ($status_code == '091') {
                        // Not Delivered
                        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
                        $shipment = $this->trackingNumber->getShipmentFromTrackingNumber($con_no);

                        /** @var \OnitsukaTiger\KerryConNo\Model\ShippingTrackHistory $model */
                        $model = $this->shippingTrackHistoryFactory->create();
                        $model->setConNo($con_no);
                        $model->setOrderId($shipment->getIncrementId());
                        $model->setStatus('Delivery Failed');
                        $model->setDescription($status);
                        $model->setServiceCode($status_code);
                        $model->setCreateTime($status_date);
                        $model->setUpdateTime($update_date);
                        $this->shippingTrackHistoryResource->save($model);

                        // make RMA request
                        $this->notDelivered->makeNotDeliveredRequest($shipment, $con_no);

                        $kerryResponseStatusDetail = new KerryResponseStatusDetail('000', 'Successfully');

                        $this->logger->info("Query Insert shipping status Successfully");

                        if ($shipment) {
                            $this->shipmentStatusModel->updateStatus($shipment, ShipmentStatus::STATUS_DELIVERY_FAILED);
                            $this->orderStatusModel->setOrderStatus($order);
                        }
                        $this->setCurrentShipment($shipment)->addstatus($order->getId(), 'Delivery Failed', $status, $status);
                    } else {
                        $kerryResponseStatusDetail = new KerryResponseStatusDetail('000', 'Successfully');
                    }
                } catch (Exception $e) {
                    $err = true;
                    $this->logger->err($e->getMessage());
                }
            }

            if ($err) {
                $kerryResponseStatusDetail = new KerryResponseStatusDetail('999', 'Unsuccessfully');
            }
        } else {
            $kerryResponseStatusDetail = new KerryResponseStatusDetail('999', 'Unsuccessfully');

            $this->logger->err(json_encode($data));
        }

        $kerryResponseStatus = new KerryResponseStatus($kerryResponseStatusDetail);
        $result = new KerryResponse($kerryResponseStatus);
        $this->logger->info($result->toString());

        return $result;
    }

    public function addstatus($order_id,$title,$tracking,$desc) {
        // Load up the order
        $order = $this->_orderRepository->get($order_id);
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $this->getCurrentShipment();
        if ($order->canShip()) {

            $order->addStatusHistoryComment('Shipping Status : ['.$desc.']', false);
            $order->save();

            $sqladdStatus="INSERT INTO sales_shipment_track (parent_id,order_id, track_number, description, title, carrier_code)";
            $sqladdStatus.="VALUES ('".$shipment->getId()."', '".$order_id."', '".$tracking."', '".$desc."', '".$title."', '".$order->getShippingMethod()."')";
            $this->resourceConnection->getConnection()->query($sqladdStatus);
        } else {
            $this->logger->info("Shipment Not Created Because It's already created or something went wrong ");
        }
    }

    /**
     * @param $shipment
     * @return $this
     */
    public function setCurrentShipment($shipment)
    {
        $this->currentShipment = $shipment;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCurrentShipment()
    {
        return $this->currentShipment;
    }
}
