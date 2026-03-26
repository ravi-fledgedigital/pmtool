<?php
declare(strict_types=1);

namespace OnitsukaTiger\CancelShipment\Model\Shipment;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use OnitsukaTiger\CancelShipment\Model\Shipment\Delete as ShipmentDelete;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTiger\CancelShipment\Model\LastTimeReject;
use OnitsukaTiger\CancelShipment\Model\LocationReject;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping;
use OnitsukaTigerKorea\Sales\Helper\Data as OnitsukaTigerKoreaData;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\Export\Cancel as OnitsukaTigerKoreaCancel;
use OnitsukaTiger\Store\Helper\Data;
use Magento\Framework\Registry;


/**
 * Class Cancel
 * @package OnitsukaTiger\CancelShipment\Model\Shipment
 */
class Cancel
{
    const AFTER_CANCEL_SHIPMENT = 'after_cancel_shipment';

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var OrderStatus
     */
    protected $orderStatusModel;

    /**
     * @var ShipmentDelete
     */
    protected $shipmentDelete;

    /**
     * @var LastTimeReject
     */
    protected $lastTimeRejectModel;

    /**
     * @var SourceRepositoryInterface
     */
    protected $sourceRepository;

    /**
     * @var LocationReject
     */
    protected $locationRejectModel;

    /**
     * @var StoreShipping
     */
    protected $storeShipping;

    /**
     * @var OnitsukaTigerKoreaData
     */
    protected $dataHelper;

    /**
     * @var OnitsukaTigerKoreaCancel
     */
    protected $sftpCancel;

    /**
     * @var Data
     */
    protected $storeManager;

    /**
     * Cancel constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param LocationReject $locationReject
     * @param LastTimeReject $lastTimeReject
     * @param OrderStatus $orderStatusModel
     * @param Delete $shipmentDelete
     * @param SourceRepositoryInterface $sourceRepository
     * @param StoreShipping $storeShipping
     * @param OnitsukaTigerKoreaData $dataHelper
     * @param OnitsukaTigerKoreaCancel $sftpCancel
     * @param Data $storeManager
     * @param Registry $registry
     */
    public function __construct(
        OrderRepositoryInterface  $orderRepository,
        LocationReject            $locationReject,
        LastTimeReject            $lastTimeReject,
        OrderStatus               $orderStatusModel,
        ShipmentDelete            $shipmentDelete,
        SourceRepositoryInterface $sourceRepository,
        StoreShipping             $storeShipping,
        OnitsukaTigerKoreaData    $dataHelper,
        OnitsukaTigerKoreaCancel  $sftpCancel,
        Data                      $storeManager,
        Registry                  $registry
    ) {
        $this->orderRepository = $orderRepository;
        $this->locationRejectModel = $locationReject;
        $this->lastTimeRejectModel = $lastTimeReject;
        $this->orderStatusModel = $orderStatusModel;
        $this->shipmentDelete = $shipmentDelete;
        $this->sourceRepository = $sourceRepository;
        $this->storeShipping = $storeShipping;
        $this->dataHelper = $dataHelper;
        $this->sftpCancel = $sftpCancel;
        $this->storeManager = $storeManager;
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);
    }

    /**
     * @param ShipmentInterface $shipment
     * @param bool|string|null $isPartialCancel
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute(ShipmentInterface $shipment, bool|null|string $isPartialCancel = false): void
    {
        $order = $shipment->getOrder();
        $sourceDeductionProcessor = true;
        if(
            ($this->storeShipping->enabledModuleStoreShipping($shipment->getStoreId()) &&
                !$this->storeShipping->isShippingFromWareHouse($shipment->getExtensionAttributes()->getSourceCode())) ||
            $this->dataHelper->isSalesEnabled($shipment->getStoreId())
        ) {
            $sourceDeductionProcessor = false;
        }

        $this->shipmentDelete->execute($shipment, $sourceDeductionProcessor);
        $this->lastTimeRejectModel->addLastTimeReject($order);
        $this->orderStatusModel->setComment($this->generateComment($shipment));
        $this->orderStatusModel->setOrderStatus($order);
        $this->locationRejectModel->addLocationReject($order, $shipment->getExtensionAttributes()->getSourceCode());

        if ($this->dataHelper->isSalesEnabled($shipment->getStoreId())) {
            if($shipment->getStoreId() == \OnitsukaTiger\Store\Model\Store::KO_KR &&
                $order->getOrderSynced()  && ($order->getCancelXmlSynced() < 2) && !$isPartialCancel
            ) {
                $this->sftpCancel->execute($shipment);
                $order->setCancelXmlSynced($order->getCancelXmlSynced() + 1);
                $this->orderRepository->save($order);
            }
        }
    }

    /**
     * @param ShipmentInterface $shipment
     * @return string
     * @throws NoSuchEntityException
     */
    public function generateComment(ShipmentInterface $shipment): string
    {
        $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
        $source = $this->sourceRepository->get($sourceCode);
        $itemInfo = [];
        foreach ($shipment->getItems() as $item) {
            $itemInfo[] = '<br> Item SKU: ' . $item->getSku() . ', Quantity: ' . (float)$item->getQty();
        }
        if ($this->dataHelper->isSalesEnabled($shipment->getStoreId())) {
            return $source->getName() . ' rejected #' . $shipment->getIncrementId() . ' (ID: '. $shipment->getEntityId() .')'  . ' ' . implode('', $itemInfo);
        }
        return $source->getName() . ' rejected #' . $shipment->getIncrementId() . ' ' . implode('', $itemInfo);
    }
}
