<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\SftpImportExport\Model\SftpImport;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\ShipmentTrackRepositoryInterface;
use OnitsukaTiger\Logger\Api\Logger;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpImport;

/**
 * Class ImportComplete | need to update shipment (order) status to Delivered.
 * @package OnitsukaTigerKorea\SftpImportExport\Model\SftpImport
 */
class ImportComplete extends SftpImport
{

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var OrderStatus
     */
    protected $orderStatusModel;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ShipmentTrackRepositoryInterface
     */
    protected $shipmentTrackRepository;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;

    /**
     * ImportShipping constructor.
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param OrderStatus $orderStatusModel
     * @param ManagerInterface $eventManager
     * @param Logger $logger
     * @param ShipmentTrackRepositoryInterface $shipmentTrackRepository
     * @param ResourceConnection $resourceConnection
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        ShipmentRepositoryInterface $shipmentRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderStatus $orderStatusModel,
        ManagerInterface $eventManager,
        Logger $logger,
        ShipmentTrackRepositoryInterface $shipmentTrackRepository,
        ResourceConnection $resourceConnection,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderStatusModel = $orderStatusModel;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
        $this->shipmentTrackRepository = $shipmentTrackRepository;
        $this->resourceConnection = $resourceConnection;
        $this->orderRepository = $orderRepository;
        parent::__construct($searchCriteriaBuilder, $shipmentRepository);
    }

    /**
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        if ($data) {
            $result['Shipment'] = [];
            foreach ($data as $shipment) {
                $shipmentId = $this->removePrefix($shipment['order_no']);
                $orderId = $this->removePrefix($shipment['origin_order_no']);
                $adv_complete_date = $shipment['adv_complete_date'];
                try {
                    $shipment = $this->getShipmentByIdWithSearchCriteria(trim($shipmentId));
                    if (is_null($shipment)) {
                        $orderRepository = $this->orderRepository->get($orderId);
                        $orderRepository->setStatus(OrderStatus::STATUS_DELIVERED);
                        $this->orderRepository->save($orderRepository);
                        continue;
                    }
                    if ($this->validateShipment($shipment, [ShipmentStatus::STATUS_SHIPPED])) {
                        $result['Shipment'][$shipmentId] = $this->updateShipmentStatusToDelivered($shipment, $adv_complete_date);
                    } else {
                        $result['Shipment'][$shipmentId] = [
                            'status' => 'fail',
                            'message' => sprintf('Message: shipment id [%s] is not status[%s]', $shipment->getIncrementId(), implode(', ', [ShipmentStatus::STATUS_SHIPPED]))
                        ];
                        $this->logger->debug(sprintf('Message: shipment id [%s] is not status [%s]', $shipment->getEntityId(), implode(', ', [ShipmentStatus::STATUS_SHIPPED])));
                    }
                } catch (\Exception $e) {
                    $result['Shipment'][$shipmentId] = [
                        'status' => 'fail',
                        'message' => 'Message:' . $e->getMessage()
                    ];
                    $this->logger->debug(sprintf('Shipment id [%s] has something wrong: [%s]', $shipmentId, $e->getMessage()));
                }
            }
            return $result;
        }
        return [];
    }

    /**
     * @param ShipmentInterface $shipment
     * @param $adv_complete_date
     * @return array
     * @throws \Exception
     */
    protected function updateShipmentStatusToDelivered(ShipmentInterface $shipment, $adv_complete_date): array
    {
        $ext = $shipment->getExtensionAttributes();
        $ext->setStatus(OrderStatus::STATUS_DELIVERED);
        $shipment->setExtensionAttributes($ext);
        $this->shipmentRepository->save($shipment);
        $this->orderStatusModel->setOrderStatus($shipment->getOrder());
        $trackItems = $shipment->getTracks();
        if (!empty($trackItems)) {
            $track = $trackItems[array_key_last($trackItems)];
            if (!is_null($track)) {
                $connection = $this->resourceConnection->getConnection();
                $table = $connection->getTableName('sales_shipment_track');
                $id = $track->getEntityId();
                $query = "UPDATE `" . $table . "` SET `updated_at` = '$adv_complete_date' WHERE `entity_id` = $id";
                $connection->query($query);
            }
        }

        // dispatch event
        $this->eventManager->dispatch(self::EVENT_SEND_EMAIL_DELIVERED, ['shipment' => $shipment]);

        $this->logger->debug(sprintf('Message: Update shipment [%s] status to delivered successfully', $shipment->getEntityId()));
        return [
            'status' => 'success',
            'message' => 'Message: Update shipment status to delivered successfully'
        ];
    }
}
