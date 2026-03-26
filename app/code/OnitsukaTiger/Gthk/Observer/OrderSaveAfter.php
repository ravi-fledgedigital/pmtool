<?php
namespace OnitsukaTiger\Gthk\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Framework\DB\Transaction;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTiger\Shipment\Model\CoreEventHandle;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Shipping\Model\ShipmentNotifier;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order\ShipmentFactory;
use Exception;

class OrderSaveAfter implements ObserverInterface
{
    protected CreditmemoFactory $creditmemoFactory;
    protected CreditmemoService $creditmemoService;
    protected Transaction $transaction;
    protected LoggerInterface $logger;
    protected Curl $curl;
    protected OrderRepositoryInterface $orderRepository;
    protected ShipmentRepositoryInterface $shipmentRepository;
    protected ShipmentNotifier $shipmentNotifier;
    protected TrackFactory $trackFactory;
    protected ConvertOrder $convertOrder;
    protected ShipmentFactory $shipmentFactory;
    protected \OnitsukaTiger\Gthk\Service\ApiService $gthkService;
    protected TrackFactory $shipmentTrackFactory;
    protected CoreEventHandle $coreEventHandle;
    private ScopeConfigInterface $scopeConfig;
    private Shipment $shipmentCollection;

    public function __construct(
        CreditmemoFactory $creditmemoFactory,
        CreditmemoService $creditmemoService,
        Transaction $transaction,
        Curl $curl,
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository,
        ShipmentNotifier $shipmentNotifier,
        ConvertOrder $convertOrder,
        \OnitsukaTiger\Gthk\Service\ApiService $gthkService,
        TrackFactory $shipmentTrackFactory,
        CoreEventHandle $coreEventHandle,
        ShipmentFactory $shipmentFactory,
        \Magento\Sales\Model\Order\ShipmentRepository $shipmentRepository,
        ScopeConfigInterface                                 $scopeConfig,
        \Magento\Sales\Model\Order\Shipment $shipmentCollection,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
    ) {
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->transaction = $transaction;
        $this->logger = $logger;
        $this->curl = $curl;
        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->convertOrder = $convertOrder;
        $this->shipmentFactory = $shipmentFactory;
        $this->gthkService = $gthkService;
        $this->shipmentTrackFactory = $shipmentTrackFactory;
        $this->coreEventHandle = $coreEventHandle;
        $this->trackFactory = $trackFactory;
        $this->scopeConfig = $scopeConfig;
        $this->shipmentCollection = $shipmentCollection;
    }

    /**
     * Observer entry point.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/gthk_api_order_save.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        $storeId = $order->getStoreId();
        $isEnabled = $this->scopeConfig->isSetFlag('onitsukatiger_gthk/general/enabled', ScopeInterface::SCOPE_STORE, $storeId);
        if ($isEnabled) {
            try {
                $shipment = $this->shipmentCollection->load($shipment->getId());
                $res = $this->gthkService->sendOrder($shipment);
                if (isset($res['tracking_id'])) {
                    $updateTrackOrder = $this->updateTrackOrder($shipment, $res['tracking_id']);
                    if ($updateTrackOrder) {
                        $logger->info("update track order: " . json_encode($updateTrackOrder));
                        $this->coreEventHandle->eventHandleForShipmentPacked($shipment, $this->logger);
                    }
                } else {
                    $logger->info('error with order update for shipment Id ' . $shipment->getId());
                }
            } catch (Exception $e) {
                $logger->info("Error: " . $e->getMessage());
                $this->logger->error($e->getMessage());
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
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/gthk_api_order_save.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        /*try {
            $order = $shipment->getOrder();
            $track = $this->trackFactory->create();
            $track->setCarrierCode('GTHK');
            $track->setTitle("GTHK");
            $track->setTrackNumber($trackingNumber);
            $shipment->addTrack($track);
            $this->shipmentRepository->save($shipment);
            $logger->info("update track order ");
            return $shipment;
        } catch (\Exception $e) {
            $logger->info("Error: " . $e->getMessage());
            $this->logger->error($e);
            throw new CouldNotSaveException(__($e->getMessage()));
        }*/
        try {
            $order = $shipment->getOrder();
            $data = [
                'carrier_code' => "GTHK",
                'title' => "GTHK",
                'number' => $trackingNumber,
            ];

            $track = $this->trackFactory->create()->addData($data);
            $shipment->addTrack($track)->save();
            return $shipment;
        } catch (\Exception $e) {
            $logger->info("update track order error: " . $e->getMessage());
            throw new CouldNotSaveException(__($e->getMessage()));
        }
    }
}
