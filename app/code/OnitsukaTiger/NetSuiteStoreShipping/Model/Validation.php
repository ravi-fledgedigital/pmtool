<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Model;

use Psr\Log\LoggerInterface;
use Magento\Framework\Message\ManagerInterface;
class Validation
{
    private StoreShipping $storeShipping;
    private LoggerInterface $commonLogger;
    protected $isShop = false;
    private ManagerInterface $manager;

    /**
     * @param StoreShipping $storeShipping
     * @param LoggerInterface $commonLogger
     * @param ManagerInterface $manager
     */
    public function __construct(
        StoreShipping $storeShipping,
        LoggerInterface $commonLogger,
        ManagerInterface    $manager
    ) {
        $this->storeShipping = $storeShipping;
        $this->commonLogger = $commonLogger;
        $this->manager = $manager;
    }
    /**
     * @param \Magento\Sales\Api\Data\ShipmentInterface $shipment
     * @param array $status
     * @return void
     */
    public function validateShipment(\Magento\Sales\Api\Data\ShipmentInterface $shipment, array $status)
    {
        $ext = $shipment->getExtensionAttributes();

        if (!$this->isShop && !$this->storeShipping->isShippingFromWareHouse($ext->getSourceCode())) {
            $this->manager->addErrorMessage(__('Shipment id '.$shipment->getIncrementId().' is not belong to warehouse'));

        }
        if (!in_array($ext->getStatus(), $status)) {
            $this->manager->addErrorMessage(__('Shipment id '.$shipment->getIncrementId().' is not status '.implode(', ', $status)));

        }
    }
}
