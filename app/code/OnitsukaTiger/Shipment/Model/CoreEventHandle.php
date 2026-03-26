<?php
namespace OnitsukaTiger\Shipment\Model;

use Exception;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\Shipment;
use OnitsukaTiger\EmailToWareHouse\Model\Email;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping;

/**
 * Class CoreEventHandle
 * @package OnitsukaTiger\Shipment\Model
 */
class CoreEventHandle
{
    const EVENT_UPDATE_ORDER_STATUS_PACKED = 'update_order_status_packed';

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var Email
     */
    protected $emailModel;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var StoreShipping
     */
    protected $storeShipping;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * CoreEventHandle constructor.
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param Email $emailModel
     * @param ManagerInterface $eventManager
     * @param StoreShipping $storeShipping
     */
    public function __construct(
        ShipmentRepositoryInterface $shipmentRepository,
        OrderRepositoryInterface $orderRepository,
        Email $emailModel,
        ManagerInterface $eventManager,
        StoreShipping $storeShipping
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->orderRepository = $orderRepository;
        $this->emailModel = $emailModel;
        $this->eventManager = $eventManager;
        $this->storeShipping = $storeShipping;
    }

    /**
     * @param Shipment $shipment
     * @param $logger
     */
    public function eventHandleForShipmentPacked(Shipment $shipment, $logger)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/core_event_handle.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("================================================================");
        if (!$shipment->getData('shipment_number')) {
            $this->saveShipmentNumber($shipment);
        }

        try {
            $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
            $logger->info("SOURCE CODE: " . $sourceCode);
            if ($this->storeShipping->isShippingFromWareHouse($sourceCode)) {
                $resultSendEmail = $this->emailModel->sendEmailToWareHouse($shipment);
                if ($resultSendEmail) {
                    $logger->info("Send Email To WareHouse Successfully.");
                } else {
                    $logger->info("Send Email To WareHouse Failed.");
                }
            }
        } catch (Exception $e) {
            $logger->info("ERROR: " . $e->getMessage());
            $logger->error($e->getMessage());
        }
        // dispatch event
        $this->eventManager->dispatch(self::EVENT_UPDATE_ORDER_STATUS_PACKED, ['shipment' => $shipment]);
        $logger->info("Dispatch packed event Successfully.");
    }

    /**
     * @param $shipment
     */
    public function saveShipmentNumber($shipment)
    {
        $order = $this->orderRepository->get($shipment->getOrderId());
        $shipmentNumberIncrement = $order->getData('order_shipment_number_increment') + 1;
        $order->setData('order_shipment_number_increment', $shipmentNumberIncrement);
        $this->orderRepository->save($order);
        $shipment->setData('shipment_number', $shipmentNumberIncrement);
        $this->shipmentRepository->save($shipment);
    }

}
