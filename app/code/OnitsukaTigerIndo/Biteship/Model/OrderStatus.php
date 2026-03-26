<?php
/**
 * phpcs:ignoreFile
 */
namespace OnitsukaTigerIndo\Biteship\Model;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use Magento\Sales\Model\ResourceModel\Status\CollectionFactory as OrderStatusCollectionFactory;

class OrderStatus
{
    /**
     * Order status
     */
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PREPACKED = 'prepacked';
    public const STATUS_PACKED = 'packed';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_STOCK_PENDING = 'stock_pending';
    public const STATUS_DELIVERY_FAILED = 'delivery_failed';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_PICKED_BY_CUSTOMER = 'picked_by_customer';
    public const STATUS_READY_TO_PICKUP = 'ready_to_pickup';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_HOLD = 'holded';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    /**
     * Order States
     */
    public const STATE_PACKED = 'packed';

    /**
     * Order status recent
     */
    public const STATUS_CLOSED_RECENT_PAYMENT_FAILED = 'Payment Failed';

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Shipment
     */
    private $shipment;

    /**
     * @var string
     */
    protected $comment;

    /**
     * @var boolean
     */
    private $hasSave = true;

    /**
     * @var OrderStatusCollectionFactory
     */
    private $orderStatusCollectionFactory;

    /**
     * Constructs a new instance.
     *
     * @param      \Magento\Sales\Api\OrderRepositoryInterface  $orderRepository               The order repository
     * @param      \OnitsukaTiger\OrderStatus\Model\Shipment    $shipment                      The shipment
     * @param      OrderStatusCollectionFactory $orderStatusCollectionFactory  The order status collection factory
     * @param      protectedScopeConfigInterface                $scopeConfig                   The scope configuration
     * @param      protected\Magento\Customer\Model\Session     $customerSession               The customer session
     * @param      protected\Cpss\Crm\Model\CpssApiRequest      $cpssApiRequest                The cpss api request
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        \OnitsukaTiger\OrderStatus\Model\Shipment $shipment,
        OrderStatusCollectionFactory $orderStatusCollectionFactory,
        protected ScopeConfigInterface $scopeConfig,
        protected \Magento\Customer\Model\Session $customerSession,
        protected \Cpss\Crm\Model\CpssApiRequest $cpssApiRequest
    ) {
        $this->orderRepository = $orderRepository;
        $this->shipment = $shipment;
        $this->orderStatusCollectionFactory = $orderStatusCollectionFactory;
    }

    /**
     * Returns an option array representation of the object.
     *
     * @return     array  Option array representation of the object.
     */
    public function toOptionArray()
    {
        return [
            [
                'status' => static::STATUS_PROCESSING,
                'updated_by' => __('Event: User ordered')
            ],[
                'status' => static::STATUS_PREPACKED,
                'updated_by' => __('Event: Received prepacked status from Biteship or Store')
            ],[
                'status' => static::STATUS_PACKED,
                'updated_by' => __('Event: Received packed status from Biteship or Store')
            ],[
                'status' => static::STATUS_SHIPPED,
                'updated_by' => __('Event: Received shipped status from Biteship or Store')
            ],[
                'status' => static::STATUS_DELIVERED,
                'updated_by' => __('Event: Received delivered status from Biteship')
            ],[
                'status' => static::STATUS_STOCK_PENDING,
                'updated_by' => __('Event: Create shipment')
            ],[
                'status' => static::STATUS_DELIVERY_FAILED,
                'updated_by' => __('Event: Received delivery failed status from Biteship')
            ],[
                'status' => static::STATUS_PICKED_BY_CUSTOMER,
                'updated_by' => __('Event: The order has been picked up by customer.')
            ],[
                'status' => static::STATUS_READY_TO_PICKUP,
                'updated_by' => __('Event: The order has been ready to pickup.')
            ],[
                'status' => static::STATUS_CANCELED,
                'updated_by' => __('Event: The order has been cancel by Biteship.')
            ],[
                'status' => static::STATUS_HOLD,
                'updated_by' => __('Event: The order has been on hold by Biteship.')
            ],[
                'status' => static::STATUS_OUT_FOR_DELIVERY,
                'updated_by' => __('Event: The order has been on way to customer.')
            ]
        ];
    }

    /**
     * Returns an array representation of the object.
     *
     * @return     <type>  Array representation of the object.
     */
    public function toArray()
    {
        $options = $this->toOptionArray();

        return array_combine(
            array_column($options, 'status'),
            array_column($options, 'updated_by')
        );
    }

    /**
     * Gets the highest status of shipments.
     *
     * @param      array   $shipments  The shipments
     *
     * @return     <type>  The highest status of shipments.
     */
    public function getHighestStatusOfShipments(array $shipments)
    {
        foreach (ShipmentStatus::getStatusPriority() as $shipmentStatus) {
            foreach ($shipments as $shipment) {
                if ($shipment->getExtensionAttributes()->getStatus() == $shipmentStatus) {
                    return $shipmentStatus;
                }
            }
        }
    }

    /**
     * Sets the order status.
     *
     * @param      \Magento\Sales\Model\Order  $order  The order
     */
    public function setOrderStatus(Order $order)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/stock_pending.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $shipments = $this->shipment->getShipmentsByOrderId($order->getId());
        $shipmentStatus = $this->getHighestStatusOfShipments($shipments);
        $state = $this->getStateByStatus($shipmentStatus);
        $logger->info("has invoice  - ".$order->hasInvoices());
        $logger->info("canShip  - ".$order->canShip());
        $logger->info("shipmentStatus  - ".$shipmentStatus);

        if (in_array($shipmentStatus, ['shipped', 'delivered'])) {
            $isCustomerNotified = 1;
        } else {
            $isCustomerNotified = 0;
        }

        $commentArray = $commentArray ?? [];
        $shipmentStatus = $shipmentStatus ?? ''; 
        $comment = $this->comment ?? ($commentArray[$shipmentStatus] ?? '');
        if (!$order->canCreditmemo() && !$order->canShip() && $this->isAllowedCloseOrder($order)) {
            $order->setStatus(static::STATUS_CLOSED)
                ->addStatusToHistory($order->getStatus(), $this->comment);
        } elseif ($order->hasInvoices() && $order->canShip()) {
            $logger->info("stock pending status - inside condition called");
            $order->setStatus(static::STATUS_STOCK_PENDING)
                ->addStatusToHistory($order->getStatus(), $comment);
        } elseif (count($shipments)) {
            $order->setStatus($shipmentStatus)->setState($state)
                ->addStatusToHistory($order->getStatus(), $comment, $isCustomerNotified);
        } else {
            // set order status by magento default behavior
        }

        if ($this->hasSave) {
            $this->orderRepository->save($order);
        }
    }

    /**
     * Sets the comment.
     *
     * @param      <type>  $comment  The comment
     *
     * @return     self    ( description_of_the_return_value )
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Determines if save.
     *
     * @param      bool  $flag   The flag
     *
     * @return     self  True if save, False otherwise.
     */
    public function hasSave(bool $flag)
    {
        $this->hasSave = $flag;
        return $this;
    }

    /**
     * Gets the state by status.
     *
     * @param      <type>  $status  The status
     *
     * @return     <type>  The state by status.
     */
    public function getStateByStatus($status)
    {
        $orderStatusCol =  $this->orderStatusCollectionFactory->create()
            ->addAttributeToFilter('main_table.status', ['eq'=>$status]);
        if ($orderStatusCol->getSize()) {
            return is_null(
                $orderStatusCol->getFirstItem()->getState()
            ) ? self::STATUS_PROCESSING : $orderStatusCol->getFirstItem()->getState();
        }
        return self::STATUS_PROCESSING;
    }

    /**
     * Determines whether the specified order is allowed close order.
     *
     * @param      \Magento\Sales\Model\Order  $order  The order
     *
     * @return     bool                        True if the specified order is allowed close order, False otherwise.
     */
    public function isAllowedCloseOrder(Order $order)
    {
        $isAllowed = false;
        $qtyRefunded = 0;
        foreach ($order->getItems() as $item) {
            if (!$item->getParentItemId()) {
                $qtyRefunded += $item->getQtyRefunded();
            }
        }

        if ($qtyRefunded == $order->getTotalQtyOrdered()) {
            $isAllowed = true;
        }
        return $isAllowed;
    }
}
