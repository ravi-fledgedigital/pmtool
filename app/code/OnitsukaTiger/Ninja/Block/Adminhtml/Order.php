<?php


namespace OnitsukaTiger\Ninja\Block\Adminhtml;


class Order extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \OnitsukaTiger\Ninja\Model\OrderFactory
     */
    protected $orderFactory;
    /**
     * @var \OnitsukaTiger\Ninja\Model\ResourceModel\Order
     */
    protected $orderResource;
    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;
    /**
     * @var \OnitsukaTiger\Ninja\Model\Order
     */
    protected $order;
    /**
     * @var \OnitsukaTiger\Ninja\Model\ResourceModel\StatusHistory\CollectionFactory
     */
    protected $statusHistoryFactory;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \OnitsukaTiger\Ninja\Model\OrderFactory $orderFactory,
        \OnitsukaTiger\Ninja\Model\ResourceModel\Order $orderResource,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \OnitsukaTiger\Ninja\Model\ResourceModel\StatusHistory\CollectionFactory $statusHistoryFactory,
        array $data = [],
    ) {
        $this->orderFactory = $orderFactory;
        $this->orderResource = $orderResource;
        $this->shipmentRepository = $shipmentRepository;
        $this->statusHistoryFactory = $statusHistoryFactory;
        parent::__construct($context, $data);

        $id = $this->_request->getParam('order_id');
        $order = $this->orderFactory->create();
        $this->orderResource->load($order, $id);

        $this->order = $order;

    }

    /**
     * Get Order
     * @return \OnitsukaTiger\Ninja\Model\Order
     */
    public function getOrder() {
        return $this->order;
    }

    /**
     * Get Shipment
     * @return \Magento\Sales\Api\Data\ShipmentInterface
     */
    public function getShipment() {
        return $this->shipmentRepository->get($this->order->getShipmentId());
    }

    public function getHistories() {
        return $this->statusHistoryFactory
            ->create()
            ->addFieldToFilter('tracking_id', $this->order->getTrackingId());
    }
}
