<?php
namespace OnitsukaTiger\Shipment\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ShipmentStatus
 * @package OnitsukaTiger\Shipment\Model
 */
class ShipmentStatus
{
    /**
     * Shipment status
     */
    const STATUS_PROCESSING = 'processing';
    const STATUS_PREPACKED = 'prepacked';
    const STATUS_PACKED = 'packed';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_DELIVERY_FAILED = 'delivery_failed';
    const STATUS_PICKED_BY_CUSTOMER = 'picked_by_customer';
    const STATUS_READY_TO_PICKUP = 'ready_to_pickup';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_HOLD = 'holded';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ShipmentStatus constructor.
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ShipmentRepositoryInterface $shipmentRepository,
        LoggerInterface $logger
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentRepository = $shipmentRepository;
        $this->logger = $logger;
    }

    /**
     * @return string[]
     */
    public static function getStatusPriority()
    {
        return [
            static::STATUS_PREPACKED,
            static::STATUS_DELIVERY_FAILED,
            static::STATUS_PROCESSING,
            static::STATUS_PACKED,
            static::STATUS_SHIPPED,
            static::STATUS_DELIVERED,
            static::STATUS_READY_TO_PICKUP,
            static::STATUS_PICKED_BY_CUSTOMER,
            static::STATUS_CANCELED,
            static::STATUS_HOLD,
            static::STATUS_OUT_FOR_DELIVERY
        ];
    }

    /**
     * @param ShipmentInterface $shipment
     * @param $status
     * @return ShipmentInterface|null
     */
    public function updateStatus($shipment, $status)
    {
        $shipment->getExtensionAttributes()->setStatus($status);
        try {
            $result = $this->shipmentRepository->save($shipment);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $result = null;
        }
        return $result;
    }

    /**
     * Shipment by Order id
     *
     * @param int $orderId
     * @return ShipmentInterface[]|null |null
     */
    public function getShipmentDataByOrderId($orderId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('order_id', $orderId)->create();
        try {
            $shipments = $this->shipmentRepository->getList($searchCriteria);
            $shipmentRecords = $shipments->getItems();
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $shipmentRecords = null;
        }
        return $shipmentRecords;
    }
}
