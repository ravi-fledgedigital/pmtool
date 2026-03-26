<?php
namespace OnitsukaTiger\OrderStatus\Model;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Status\CollectionFactory as OrderStatusCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;

class OrderStatus
{
    /**
     * Order status processing
     */
    public const STATUS_PROCESSING = 'processing';
    /**
     * Order status prepacked
     */
    public const STATUS_PREPACKED = 'prepacked';
    /**
     * Order status packed
     */
    public const STATUS_PACKED = 'packed';
    /**
     * Order status shipped
     */
    public const STATUS_SHIPPED = 'shipped';
    /**
     * Order status delivered
     */
    public const STATUS_DELIVERED = 'delivered';
    /**
     * Order status stock_pending
     */
    public const STATUS_STOCK_PENDING = 'stock_pending';
    /**
     * Order status delivery_failed
     */
    public const STATUS_DELIVERY_FAILED = 'delivery_failed';
    /**
     * Order status closed
     */
    public const STATUS_CLOSED = 'closed';
    /**
     * Order status picked_by_customer
     */
    public const STATUS_PICKED_BY_CUSTOMER = 'picked_by_customer';
    /**
     * Order status ready_to_pickup
     */
    public const STATUS_READY_TO_PICKUP = 'ready_to_pickup';

    /**
     * Order States packed
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
     * @param OrderRepositoryInterface $orderRepository
     * @param Shipment $shipment
     * @param OrderStatusCollectionFactory $orderStatusCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Cpss\Crm\Model\CpssApiRequest $cpssApiRequest
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Shipment $shipment,
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
     * Option Array
     *
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'status' => static::STATUS_PROCESSING,
                'updated_by' => __('Event: User ordered')
            ],[
                'status' => static::STATUS_PREPACKED,
                'updated_by' => __('Event: Received prepacked status from Netsuite or Store')
            ],[
                'status' => static::STATUS_PACKED,
                'updated_by' => __('Event: Received packed status from Netsuite or Store')
            ],[
                'status' => static::STATUS_SHIPPED,
                'updated_by' => __('Event: Received shipped status from NetSuite or Store')
            ],[
                'status' => static::STATUS_DELIVERED,
                'updated_by' => __('Event: Received delivered status from Kerry, Ninja or GHTK')
            ],[
                'status' => static::STATUS_STOCK_PENDING,
                'updated_by' => __('Event: Create shipment')
            ],[
                'status' => static::STATUS_DELIVERY_FAILED,
                'updated_by' => __('Event: Received delivery failed status from Kerry, Ninja or GHTK')
            ],[
                'status' => static::STATUS_PICKED_BY_CUSTOMER,
                'updated_by' => __('Event: The order has been picked up by customer.')
            ],
            [
                'status' => static::STATUS_READY_TO_PICKUP,
                'updated_by' => __('Event: The order has been ready to pickup.')
            ]
        ];
    }

    /**
     * To Array function
     *
     * @return array
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
     * Highest Status Of Shipments
     *
     * @param ShipmentInterface[] $shipments
     * @return mixed
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
     * Setting Order Status
     *
     * @param Order $order
     * @return false
     */
    public function setOrderStatus(Order $order)
    {
        $shipments = $this->shipment->getShipmentsByOrderId($order->getId());
        $shipmentStatus = $this->getHighestStatusOfShipments($shipments);
        $state = $this->getStateByStatus($shipmentStatus);

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/orderStatus.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $orderStatusArray = [];
        $orderStatusArray['Shipment Status'] = $shipmentStatus;
        $orderStatusArray['State'] = $state;

        if (!$order->canCreditmemo() && !$order->canShip() && $this->isAllowedCloseOrder($order)) {
            $order->setStatus(static::STATUS_CLOSED)
                ->addStatusToHistory($order->getStatus(), $this->comment);
        } elseif ($order->hasInvoices() && $order->canShip()) {
            $order->setStatus(static::STATUS_STOCK_PENDING)
                ->addStatusToHistory($order->getStatus(), $this->comment ??
                    $this->toArray()[$shipmentStatus]);
        } elseif (count($shipments)) {
            $orderStatusArray['Inside else if'] = 'True';
            $order->setStatus($shipmentStatus)->setState($state)
                ->addStatusToHistory($order->getStatus(), $this->comment ??
                    $this->toArray()[$shipmentStatus]);
        }
        $logger->info('Order Array: ' . json_encode($orderStatusArray));
        if ($this->hasSave) {
            $this->orderRepository->save($order);
        }
    }

    /**
     * Set comment function
     *
     * @param mixed $comment
     * @return $this
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Has Save function
     *
     * @param bool $flag
     * @return $this
     */
    public function hasSave(bool $flag)
    {
        $this->hasSave = $flag;
        return $this;
    }

    // phpcs:disable
    /**
     * State By Status
     *
     * @param mixed $status
     * @return mixed
     */
    public function getStateByStatus($status)
    {
        $orderStatusCol =  $this->orderStatusCollectionFactory->create()
            ->addAttributeToFilter('main_table.status', ['eq'=>$status]);
        if ($orderStatusCol->getSize()) {
            return is_null($orderStatusCol->getFirstItem()->getState()) ?
                self::STATUS_PROCESSING : $orderStatusCol->getFirstItem()->getState();
        }
        return self::STATUS_PROCESSING;
    }
    // phpcs:enable

    /**
     * Is Allowed Close Order fucntion
     *
     * @param Order $order
     * @return bool
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

    /**
     * Recover Status Canceled function
     *
     * @param Order $order
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function recoverStatusCanceled(Order $order): void
    {
        $order->setTotalCanceled(0);
        $order->setBaseTotalCanceled(0);
        $order->setDiscountCanceled(0);
        $order->setBaseDiscountCanceled(0);
        $order->setShippingCanceled(0);
        $order->setSubtotalCanceled(0);
        $order->setBaseSubtotalCanceled(0);
        $order->setTaxCanceled(0);

        foreach ($order->getAllItems() as $orderItem) {
            $orderItem->setQtyCanceled(0);
        }

        $order->setStatus(Order::STATE_PROCESSING);
        $order->setState(Order::STATE_PROCESSING);
        $order->addCommentToStatusHistory('Recover Order Canceled By Admin');

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/OrderStatusChange.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Order Status Change Start============================');

        $usedPoint = $order->getUsedPoint();
        $logger->info('Used Point: ' . $usedPoint);
        $isEnabled = $this->scopeConfig->getValue('crm/general/enable', ScopeInterface::SCOPE_STORE);
        $logger->info('Is Module Enabled: ' . $isEnabled);
        if ($isEnabled && $usedPoint > 0) {
            $memberId = $order->getCustomerId();
            $logger->info('Member ID: ' . $memberId);
            $response = $this->cpssApiRequest->usePoint($order->getIncrementId(), $memberId, $usedPoint);
            $order->setCpssSubStatus($response['X-CPSS-Result']);
        }

        $this->orderRepository->save($order);

        if (!$order->hasInvoices()) {
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
            $invoice->register();
            $order->addRelatedObject($invoice)->save();
        }

        $logger->info('==========================Order Status Change End============================');
    }
}
