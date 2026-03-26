<?php

namespace OnitsukaTiger\Ninja\Observer;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTiger\EmailToWareHouse\Model\Email;
use OnitsukaTiger\Logger\Ninja\Logger;
use OnitsukaTiger\NetSuite\Model\NetSuite;
use OnitsukaTiger\Ninja\Service\ApiService;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTiger\Shipment\Model\CoreEventHandle;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;

/**
 * Class Observer
 * @package OnitsukaTiger\Ninja\Observer
 */
class Observer implements ObserverInterface
{

    /**
     * @var ApiService
     */
    protected $ninjaApiService;

    /**
     * @var Email
     */
    protected $emailModel;

    /**
     * @var ShipmentStatus
     */
    protected $shipmentStatusModel;
    /**
     * @var OrderStatus
     */
    protected $orderStatusModel;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var TrackFactory
     */
    protected $shipmentTrackFactory;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var CoreEventHandle
     */
    protected $coreEventHandle;

    /**
     * @var NetSuite
     */
    protected $netsuiteManager;
    private ScopeConfigInterface $scopeConfig;

    /**
     * Observer constructor.
     * @param ApiService $ninjaApiService
     * @param Email $emailModel
     * @param OrderStatus $orderStatusModel
     * @param ShipmentStatus $shipmentStatusModel
     * @param Logger $logger
     * @param TrackFactory $shipmentTrackFactory
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ManagerInterface $eventManager
     * @param CoreEventHandle $coreEventHandle
     * @param NetSuite $netsuiteManager
     */
    public function __construct(
        ApiService                  $ninjaApiService,
        Email                       $emailModel,
        OrderStatus                 $orderStatusModel,
        ShipmentStatus              $shipmentStatusModel,
        Logger                      $logger,
        TrackFactory                $shipmentTrackFactory,
        ShipmentRepositoryInterface $shipmentRepository,
        ManagerInterface            $eventManager,
        CoreEventHandle             $coreEventHandle,
        ScopeConfigInterface        $scopeConfig,
        NetSuite                    $netsuiteManager
    )
    {
        $this->ninjaApiService = $ninjaApiService;
        $this->emailModel = $emailModel;
        $this->orderStatusModel = $orderStatusModel;
        $this->shipmentStatusModel = $shipmentStatusModel;
        $this->logger = $logger;
        $this->shipmentTrackFactory = $shipmentTrackFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->eventManager = $eventManager;
        $this->coreEventHandle = $coreEventHandle;
        $this->netsuiteManager = $netsuiteManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return bool
     * @throws Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();
        $storeId = $shipment->getOrder()->getStoreId();
        $enable = $this->scopeConfig->getValue('ninja/general/enabled', ScopeInterface::SCOPE_STORE, $storeId);
        if ($enable) {
            $shippingMethod = $shipment->getOrder()->getShippingMethod();
            $shipmentStatus = ($shipment->getExtensionAttributes()) ? $shipment->getExtensionAttributes()->getStatus() : '';

            $tracks = $shipment->getAllTracks();
            $firstTrack = reset($tracks);
            $trackNumber = ($firstTrack && $firstTrack->getTrackNumber()) ? true : false;
            if ($shippingMethod !== 'ninja_ninja' || $trackNumber) {
                return false;
            }
            try {
                $res = $this->ninjaApiService->sendOrder($shipment);
                $updateTrackOrder = $this->updateTrackOrder($shipment, $res['tracking_number']);
                if ($updateTrackOrder) {
                    $this->coreEventHandle->eventHandleForShipmentPacked($shipment, $this->logger);
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                $this->netsuiteManager->throwWebApiException(sprintf('Error when calling Shipping Ninja with shipment id [%s]', $shipment->getIncrementId()), 400);
            }
        }
    }

    /**
     * @param Shipment $shipment
     * @param $trackingNumber
     * @return bool|Shipment
     * @throws Exception
     */
    public function updateTrackOrder($shipment, $trackingNumber)
    {
        try {
            $order = $shipment->getOrder();
            $data = [
                'carrier_code' => $order->getShippingMethod(),
                'title' => "Ninja Van",
                'number' => $trackingNumber,
            ];

            $track = $this->shipmentTrackFactory->create()->addData($data);
            $shipment->addTrack($track)->save();
            return $shipment;
        } catch (\Exception $e) {
            $this->logger->error($e);
            throw new CouldNotSaveException(__($e->getMessage()));
        }
    }
}

