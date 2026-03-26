<?php

namespace OnitsukaTigerKorea\Sales\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Shipment;
use OnitsukaTigerKorea\Sales\Model\OrderXmlId;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\ExportXml;


class AfterSaveShipment implements ObserverInterface
{

    /**
     * @var OrderXmlId
     */
    protected $orderXmlId;

    /**
     * AfterSaveShipment constructor.
     * @param OrderXmlId $orderXmlId
     */
    public function __construct(
        OrderXmlId $orderXmlId
    ) {
        $this->orderXmlId = $orderXmlId;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();
        if ($shipment->getOrigData('entity_id')) {
            return;
        }
        $this->orderXmlId->updateOrderXmlId($shipment->getOrderId(), $shipment->getId(), ExportXml::PREFIX_SHIPMENT);
    }

}
