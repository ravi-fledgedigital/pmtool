<?php

namespace OnitsukaTiger\InventorySourceAlgorithm\Model;

use Magento\Sales\Api\Data\ShipmentExtensionFactory;

class CreateShipment {

    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    protected $_shipmentTrackFactory;

    /**
     * @var \Magento\Sales\Model\Order\ShipmentFactory
     */
    protected $_shipmentFactory;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var \OnitsukaTiger\Logger\Logger
     */
    protected $logger;

    protected $track = null;

    protected $shipmentExtensionFactory;


    /**
     * CreateShipment constructor.
     * @param \Magento\Sales\Model\Order\Shipment\TrackFactory $shipmentTrackFactory
     * @param \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param ShipmentExtensionFactory $shipmentExtensionFactory
     * @param \OnitsukaTiger\Logger\Logger $logger
     */
    public function __construct(
        \Magento\Sales\Model\Order\Shipment\TrackFactory $shipmentTrackFactory,
        \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        ShipmentExtensionFactory $shipmentExtensionFactory,
        \OnitsukaTiger\Logger\Logger $logger
    )
    {
        $this->shipmentExtensionFactory = $shipmentExtensionFactory;
        $this->_shipmentTrackFactory = $shipmentTrackFactory;
        $this->_shipmentFactory = $shipmentFactory;
        $this->_transactionFactory = $transactionFactory;
        $this->_orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * @param $order
     * @param $shipItem
     * @param $sourceCode
     * @return false
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createShipment($order, $shipItem, $sourceCode)
    {
        try {
            if ($order){
                $shipment = $this->prepareShipment($order, $shipItem);
                if ($shipment) {
                    // assign source code to shipment
                    $shipmentExtension = $shipment->getExtensionAttributes();

                    if (empty($shipmentExtension)) {
                        $shipmentExtension = $this->shipmentExtensionFactory->create();
                    }
                    $shipmentExtension->setSourceCode($sourceCode);
                    $shipment->setExtensionAttributes($shipmentExtension);

                    $order->setIsInProcess(true);
                    $order->addCommentToStatusHistory('', false);
                    $transactionSave =  $this->_transactionFactory->create()->addObject($shipment)->addObject($shipment->getOrder());
                    $transactionSave->save();
                }


                return $shipment;
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Order %s create shipment automation has error %s', $order->getIncrementId(), $e->getMessage()));
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * @param $order
     * @param $shipItem
     * @return false
     */
    protected function prepareShipment($order,$shipItem)
    {
        $track = $this->getTrack();
        $shipment = $this->_shipmentFactory->create(
            $order,
            $this->prepareShipmentItems($order,$shipItem),
            $track
        );
        return $shipment->getTotalQty() ? $shipment->register() : false;
    }

    /**
     * @param $order
     * @param $shipItem
     * @return array
     */
    protected function prepareShipmentItems($order, $shipItem)
    {

        return $shipItem;
//        $items = [];
//
//        foreach($order->getAllItems() as $item) {
//            $items[$item->getItemId()] = $item->getQtyOrdered();
//        }
//        return $items;
    }

    /**
     * @param $track
     * @return $this
     */
    public function setTrack($track)
    {
        $this->track = $track;
        return $this;
    }

    /**
     * @return null
     */
    public function getTrack()
    {
        return $this->track;
    }
}
