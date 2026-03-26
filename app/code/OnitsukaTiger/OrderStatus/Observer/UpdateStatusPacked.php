<?php
namespace OnitsukaTiger\OrderStatus\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use Magento\Framework\App\RequestInterface;
use OnitsukaTiger\Cegid\Model\ShipmentUpdate;

class UpdateStatusPacked implements ObserverInterface
{
    /**
     * @var OrderStatus
     */
    protected $orderStatusModel;

    /**
     * @var ShipmentStatus
     */
    protected $shipmentStatusModel;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param OrderStatus $orderStatusModel
     * @param ShipmentStatus $shipmentStatusModel
     * @param RequestInterface $request
     */
    public function __construct(
        OrderStatus $orderStatusModel,
        ShipmentStatus $shipmentStatusModel,
        RequestInterface $request
    ) {
        $this->orderStatusModel = $orderStatusModel;
        $this->shipmentStatusModel = $shipmentStatusModel;
        $this->request            = $request;
    }

    public function execute(Observer $observer)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/packed.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("================================================================");
        if (strpos($this->request->getRequestString(), ShipmentUpdate::ROUTES_UPDATE_SHIPMENT) === false) {
            /** @var ShipmentInterface $shipment */
            $shipment = $observer->getEvent()->getShipment();

            $this->shipmentStatusModel->updateStatus($shipment, ShipmentStatus::STATUS_PACKED);
            $logger->info("Shipment status for pack.");
            $this->orderStatusModel->setOrderStatus($shipment->getOrder());
        }
    }
}
