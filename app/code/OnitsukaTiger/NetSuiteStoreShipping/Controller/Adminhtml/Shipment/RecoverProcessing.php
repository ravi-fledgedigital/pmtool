<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\Shipment;

use Clickend\Kerry\Model\TrackingListFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\TrackRepository;
use OnitsukaTiger\Logger\Logger;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping;
use OnitsukaTiger\Ninja\Model\OrderFactory;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use Psr\Log\LoggerInterface;

class RecoverProcessing extends Action
{
    const ADMIN_RESOURCE = 'Magento_Sales::ship_recover_processing';
    private ShipmentRepositoryInterface $shipmentRepository;
    private LoggerInterface $commonLogger;
    private Logger $logger;
    private ManagerInterface $eventManager;
    private StoreShipping $storeShipping;
    private ShipmentStatus $shipmentStatusModel;
    private OrderStatus $orderStatusModel;
    private Shipment $shipment;
    private TrackRepository $trackRepository;
    private TrackingListFactory $trackingListFactory;
    private OrderFactory $ninjaOrderFactory;

    /**
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param LoggerInterface $commonLogger
     * @param Logger $logger
     * @param ManagerInterface $eventManager
     * @param StoreShipping $storeShipping
     * @param ShipmentStatus $shipmentStatusModel
     * @param OrderStatus $orderStatusModel
     * @param Shipment $shipment
     * @param TrackRepository $trackRepository
     * @param TrackingListFactory $trackingListFactory
     * @param OrderFactory $ninjaOrderFactory
     * @param Context $context
     */
    public function __construct(
        ShipmentRepositoryInterface $shipmentRepository,
        LoggerInterface $commonLogger,
        Logger  $logger,
        ManagerInterface $eventManager,
        StoreShipping $storeShipping,
        ShipmentStatus $shipmentStatusModel,
        OrderStatus $orderStatusModel,
        Shipment    $shipment,
        TrackRepository $trackRepository,
        TrackingListFactory $trackingListFactory,
        OrderFactory $ninjaOrderFactory,
        Context $context
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->commonLogger = $commonLogger;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
        $this->storeShipping = $storeShipping;
        $this->shipmentStatusModel = $shipmentStatusModel;
        $this->orderStatusModel = $orderStatusModel;
        $this->shipment = $shipment;
        $this->trackRepository = $trackRepository;
        $this->trackingListFactory = $trackingListFactory;
        $this->ninjaOrderFactory = $ninjaOrderFactory;
        parent::__construct($context);
    }

    /**
     * Recover packed to processing
     * @return ResponseInterface|ResultInterface
     * @throws CouldNotDeleteException
     */
    public function execute()
    {
        $shipmentId = $this->getRequest()->getParam("shipment_increment_id");
        $shipment = $this->shipment->loadByIncrementId($shipmentId);
        $trackData = $shipment->getTracks();
        foreach ($trackData as $item) {
            if ($item->getEntityId()) {
                $this->trackRepository->delete($item);
            }
            if ($item->getCarrierCode() == 'ninja_ninja') {
                $this->removeTrackingNumberNinja($shipment->getId());
            } else {
                $this->removeTrackingNumberKerry($shipmentId);
            }
        }
        $url = $this->getUrl('sales/shipment/view', ['shipment_id' => $shipment->getId()]);
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($url);
        try {
            $shipmentRepository = $this->shipmentRepository->get($shipment->getId());
            $order = $shipmentRepository->getOrder();
            $this->shipmentStatusModel->updateStatus($shipmentRepository, ShipmentStatus::STATUS_PROCESSING);
            $this->orderStatusModel->setOrderStatus($order);
            $this->messageManager->addSuccessMessage(__('Successfully Recover Processing'));
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error Recover Processing  shipment.'));
            $this->logger->error(sprintf('Error Recover Processing shipment. [%s]. Message: [%s]', $shipmentRepository->getIncrementId(), $e->getMessage()));
            return $resultRedirect;
        }
    }

    /**
     * Remove tracking number of Ninja
     * @param $shipmentId
     * @return void
     */
    public function removeTrackingNumberNinja($shipmentId)
    {
        $ninjaTracking = $this->ninjaOrderFactory->create()->load($shipmentId, "shipment_id");
        if ($ninjaTracking->getId()) {
            $ninjaTracking->delete();
        }
    }

    /**
     * Remove tracking number of Kerry
     * @param $shipmentIncrementId
     * @return void
     */
    public function removeTrackingNumberKerry($shipmentIncrementId)
    {
        $kerryTracking = $this->trackingListFactory->create()->load($shipmentIncrementId, "unique_id");
        if ($kerryTracking->getId()) {
            $kerryTracking->delete();
        }
    }
}
