<?php
/**
 * Copy Co-well Asia 2020
 */
namespace OnitsukaTiger\EmailToWareHouse\Observer;

use Clickend\Kerry\Helper\Data;
use Clickend\Kerry\Model\TrackingHistoryFactory;
use Clickend\Kerry\Model\TrackingListFactory;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Order\ShipmentFactory;
use OnitsukaTiger\EmailToWareHouse\Model\Email;
use OnitsukaTiger\Logger\Kerry\Logger;
use OnitsukaTiger\NetSuite\Model\NetSuite;
use OnitsukaTiger\Shipment\Model\CoreEventHandle;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;

/**
 * Class PreferenceKerryObserver
 * @package OnitsukaTiger\EmailToWareHouse\Observer
 */
class PreferenceKerryObserver implements ObserverInterface
{

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var TrackFactory
     */
    protected $_shipmentTrackFactory;

    /**
     * @var ShipmentFactory
     */
    protected $_shipmentFactory;
    /**
     * @var TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var Email
     */
    protected $emailModel;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var TrackingHistoryFactory
     */
    protected $trackingHistoryFactory;

    /**
     * @var TrackingListFactory
     */
    protected $trackingListFactory;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var CoreEventHandle
     */
    protected $coreEventHandle;

    /**
     * @var NetSuite
     */
    protected $netsuiteManager;

    /**
     * PreferenceKerryObserver constructor.
     * @param Data $dataHelper
     * @param ResourceConnection $resourceConnection
     * @param TrackFactory $shipmentTrackFactory
     * @param ShipmentFactory $shipmentFactory
     * @param TransactionFactory $transactionFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param Email $emailModel
     * @param TrackingHistoryFactory $trackingHistoryFactory
     * @param TrackingListFactory $trackingListFactory
     * @param ManagerInterface $eventManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param Logger $logger
     * @param CoreEventHandle $coreEventHandle
     * @param NetSuite $netsuiteManager
     */
    public function __construct(
        Data $dataHelper,
        ResourceConnection $resourceConnection,
        TrackFactory $shipmentTrackFactory,
        ShipmentFactory $shipmentFactory,
        TransactionFactory $transactionFactory,
        OrderRepositoryInterface $orderRepository,
        Email $emailModel,
        TrackingHistoryFactory $trackingHistoryFactory,
        TrackingListFactory $trackingListFactory,
        ManagerInterface $eventManager,
        ScopeConfigInterface $scopeConfig,
        ShipmentRepositoryInterface $shipmentRepository,
        Logger $logger,
        CoreEventHandle $coreEventHandle,
        NetSuite $netsuiteManager
    ) {
        $this->eventManager = $eventManager;
        $this->dataHelper = $dataHelper;
        $this->resourceConnection = $resourceConnection;
        $this->_shipmentTrackFactory = $shipmentTrackFactory;
        $this->_shipmentFactory = $shipmentFactory;
        $this->_transactionFactory = $transactionFactory;
        $this->_orderRepository = $orderRepository;
        $this->emailModel = $emailModel;
        $this->trackingHistoryFactory = $trackingHistoryFactory;
        $this->trackingListFactory = $trackingListFactory;
        $this->scopeConfig = $scopeConfig;
        $this->shipmentRepository = $shipmentRepository;
        $this->logger = $logger;
        $this->coreEventHandle = $coreEventHandle;
        $this->netsuiteManager = $netsuiteManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return bool
     * @throws CouldNotSaveException
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $logger = $this->logger;

        $helper = $this->dataHelper;

        $logger->info("Module Enable : " . $helper->_isEnabled());

        if ($helper->_isEnabled()) {
            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
            $shipment = $observer->getEvent()->getShipment();
            $order = $shipment->getOrder();
            $shipping = $order->getShippingMethod();

            $logger->info("=================== Shipment : " . $shipment->getId() . " Event : " . $order->getState() . "  =================");

            if ($shipping == "kerryshipping_kerryshipping" && $shipment->getExtensionAttributes()->getStatus()  == ShipmentStatus::STATUS_PREPACKED) {
                $actionCode = $this->scopeConfig->getValue('kerry/observer/action_code');

                $get_data = $helper->placeOrder($shipment, $actionCode);
                $logger->info("Con No : " . $get_data['response']['res']['shipment']['con_no']);
                $logger->info("Status Code : " . $get_data['response']['res']['shipment']['status_code']);
                $logger->info("Status Desc : " . $get_data['response']['res']['shipment']['status_desc']);

                if ($get_data['response']['res']['shipment']['status_code'] == "000") {
                    try {
                        $logger->info("Insert Shipping Data");

                        $con_no = $get_data['request']['req']['shipment']['con_no'];

                        // ผู้ส่ง
                        $s_contactperson = $get_data['request']['req']['shipment']['s_contactperson'];
                        // ผู้รับ
                        $r_contactperson = $get_data['request']['req']['shipment']['r_contactperson'];

                        $unique_id = $get_data['request']['req']['shipment']['unique_id'];

                        $trackingListModel = $this->trackingListFactory->create();
                        $trackingListModel->addData($get_data['request']['req']['shipment'])
                            ->setData('s_contact', $s_contactperson)
                            ->setData('r_contact', $r_contactperson)
                            ->save();

                        //$a=$get_data['request']['req']['shipment'][r_email];
                        $logger->info(json_encode($get_data['request']));

                        $trackingHistoryModel = $this->trackingHistoryFactory->create();
                        $trackingHistoryModel->setData('con_no', $con_no)
                            ->setData('order_id', $unique_id)
                            ->setData('status', 'New Shipping')
                            ->setData('description', 'Successfully')
                            ->setData('service_code', '000')
                            ->setData('create_time', date('Y-m-d H:i:s'))
                            ->setData('update_time', date('Y-m-d H:i:s'))
                            ->save();

                        $updateTrackOrder = $this->updateTrackOrder($shipment, $con_no);

                        if ($updateTrackOrder) {
                            $this->coreEventHandle->eventHandleForShipmentPacked($shipment, $this->logger);
                        }
                    } catch (Exception $e) {
                        $message = "ERROR Cannot insert Trackking \n" . $e->getMessage();
                        $logger->err($message);
                        throw new CouldNotSaveException(__($message));
                        return false;
                    }
                } else {
                    $message = "ERROR Can't add tracking [ Error Code : " . $get_data['response']['res']['shipment']['status_code'] . " ] | [ Description : " . $get_data['response']['res']['shipment']['status_desc'] . " ]";
                    $logger->err($message);
                    throw new CouldNotSaveException(__($message));
                    return false;
                }
            }
        }
    }

    /**
     * @param $shipment
     * @param $trackingNumber
     * @return bool
     * @throws CouldNotSaveException
     */
    public function updateTrackOrder($shipment, $trackingNumber)
    {
        try {
            $order = $shipment->getOrder();
            if ($order) {
                $data = [
                    'carrier_code' => $order->getShippingMethod(),
                    'title' => "Kerry Express",
                    'number' => $trackingNumber,
                ];

                $track = $this->_shipmentTrackFactory->create()->addData($data);
                $shipment->addTrack($track)->save();
                return $shipment;
            }
        } catch (\Exception $e) {
            $this->logger->err(sprintf('Update Track Order has error %s', $e->getMessage()));
            throw new CouldNotSaveException(__($e->getMessage()));
            return false;
        }
    }
}
