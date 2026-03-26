<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\SftpImportExport\Model\SftpImport;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpImport;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use OnitsukaTiger\Logger\Api\Logger;

/**
 * Class ImportShipping | This I/F just need to add tracking number to shipment.
 * @package OnitsukaTigerKorea\SftpImportExport\Model\SftpImport
 */
class ImportShipping extends SftpImport {

    /**
     * Event : update order status to shipped
     */
    const EVENT_UPDATE_ORDER_STATUS_SHIPPED = 'netsuite_update_order_status_shipped';

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var TrackFactory
     */
    protected $_shipmentTrackFactory;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    protected $orderStatusRepository;

    /**
     * ImportShipping constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderStatusHistoryRepositoryInterface $orderStatusRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param TrackFactory $shipmentTrackFactory
     * @param ManagerInterface $eventManager
     * @param Logger $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderStatusHistoryRepositoryInterface $orderStatusRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ShipmentRepositoryInterface $shipmentRepository,
        TrackFactory $shipmentTrackFactory,
        ManagerInterface $eventManager,
        Logger $logger
    ){
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->_shipmentTrackFactory = $shipmentTrackFactory;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
        parent::__construct($searchCriteriaBuilder, $shipmentRepository);
    }

    /**
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        if($data){
            $result['Shipment'] = [];
            foreach($data as $key => $shipment) {
                $shipmentId = $this->removePrefix($shipment['order_no']);
                $orderId = $this->removePrefix($shipment['origin_order_no']);
                $trackingNo = $shipment['tracking_no'];
                try {
                    $shipment = $this->getShipmentByIdWithSearchCriteria(trim($shipmentId));
                    $order = $this->orderRepository->get(trim($orderId));
                    if(is_null($shipment)){
                        $order->addCommentToStatusHistory(sprintf('Message: I/F Shipping - shipment id [%s] doesn\'t exist', $shipmentId));
                        $this->logger->debug(sprintf('Message: shipment id [%s] doesn\'t exist', $shipmentId));
                        $this->orderRepository->save($order);
                        continue;
                    }
                    if( $this->validateShipment($shipment, [ShipmentStatus::STATUS_PROCESSING, ShipmentStatus::STATUS_SHIPPED])){
                        $result['Shipment'][$shipmentId] =  $this->updateTrackOrder($shipment, $trackingNo);
                    }else {
                        $result['Shipment'][$shipmentId] = [
                            'status' => 'fail',
                            'message' => sprintf('Message: shipment id [%s] is not status [%s]', $shipment->getIncrementId(), implode(', ',[ShipmentStatus::STATUS_PROCESSING]))
                        ];
                        $this->logger->debug(sprintf('Message: shipment id [%s] is not status [%s]', $shipment->getIncrementId(), implode(', ',[ShipmentStatus::STATUS_PROCESSING])));
                    }
                }catch (\Exception $e) {
                    $result['Shipment'][$shipmentId] = [
                        'status' => 'fail',
                        'message' => 'Message:' . $e->getMessage()
                    ];
                    $this->logger->debug(sprintf('shipment id [%s] has something wrong: [%s]', $shipmentId, $e->getMessage()));
                }
            }
            return $result;
        }
    }

    /**
     * @param ShipmentInterface $shipment
     * @param $trackingNumber
     * @return string[]
     */
    public function updateTrackOrder(ShipmentInterface $shipment, $trackingNumber): array
    {
        try {
            /** @var OrderInterface $order */
            $order = $shipment->getOrder();
            if(count($shipment->getTracks()) > 0) {
                foreach ($shipment->getTracks() as $track) {
                    $track->setCarrierCode('post_delivery_service');
                    $track->setTitle("우체국택배");
                    $track->setTrackNumber($trackingNumber);
                    $track->save();
                }
                $order->addCommentToStatusHistory('Event: Add tracking number '. $trackingNumber. ' to shipment ' . $shipment->getIncrementId())
                    ->save();
                return [
                    'status' => 'fail',
                    'message' => 'Message: Tracking number already exist'
                ];
            }
            if ($order) {
                $data = [
                    'carrier_code' => 'post_delivery_service',
                    'title' => "우체국택배",
                    'number' => $trackingNumber,
                ];

                $track = $this->_shipmentTrackFactory->create()->addData($data);
                $shipment->addTrack($track)->save();
                $order->addCommentToStatusHistory('Event: Add tracking number '. $trackingNumber. ' to shipment ' . $shipment->getIncrementId())
                    ->save();

                $this->eventManager->dispatch(self::EVENT_UPDATE_ORDER_STATUS_SHIPPED,['shipment' => $shipment]);
                $this->logger->debug(sprintf('Message: Shipment [%s] add tracking number success', $shipment->getEntityId()));
                return [
                    'status' => 'success',
                    'message' => 'Message: Add tracking number success'
                ];
            }
        } catch (\Exception $e) {
            $this->logger->debug(sprintf('Message: Shipment [%s] add tracking number fail', $shipment->getEntityId()));
            return [
                'status' => 'fail',
                'message' => 'Message: Add tracking number fail'
            ];
        }
    }
}
