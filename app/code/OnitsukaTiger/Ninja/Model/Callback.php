<?php
/** phpcs:ignoreFile */
namespace OnitsukaTiger\Ninja\Model;

use Exception;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Store\Model\StoreRepository;
use OnitsukaTiger\KerryConNo\Model\TrackingNumber;
use OnitsukaTiger\Logger\Ninja\Logger;
use OnitsukaTiger\Ninja\Api\CallbackInterface;
use OnitsukaTiger\Ninja\Api\Response\ResponseInterface;
use OnitsukaTiger\Ninja\Model\ResourceModel\Order;
use OnitsukaTiger\Ninja\Model\ResourceModel\StatusHistory;
use OnitsukaTiger\Ninja\Model\Response\Response;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTiger\Rma\Helper\NotDelivered;
use OnitsukaTiger\Sales\Helper\Data;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;

class Callback implements CallbackInterface
{
    /**
     * Event : update order status to shipped
     */
    const EVENT_SEND_EMAIL_DELIVERED = 'netsuite_update_order_status_delivered';

    /**
     * @var Http
     */
    protected $httpRequest;

    /**
     * @var StoreRepository
     */
    protected $storeRepository;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var StatusHistoryFactory
     */
    protected $statusHistoryFactory;

    /**
     * @var ResourceModel\StatusHistory
     */
    protected $statusHistoryResource;

    protected $ninjaOrder;

    /**
     * @var TrackingNumber
     */
    protected $trackingNumber;

    /**
     * @var ShipmentStatus
     */
    protected $shipmentStatusModel;

    /**
     * @var OrderStatus
     */
    protected $orderStatusModel;

    /**
     * @var Data
     */
    protected $mailSender;

    /**
     * @var NotDelivered
     */
    protected $notDelivered;
    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * Callback constructor.
     * @param Http $httpRequest
     * @param StoreRepository $storeRepository
     * @param Logger $logger
     * @param Config $config
     * @param StatusHistoryFactory $statusHistoryFactory
     * @param StatusHistory $statusHistoryResource
     * @param ResourceModel\Order $ninjaOrder
     * @param TrackingNumber $trackingNumber
     * @param ShipmentStatus $shipmentStatusModel
     * @param OrderStatus $orderStatusModel
     * @param Data $mailSender
     * @param NotDelivered $notDelivered
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        Http $httpRequest,
        StoreRepository $storeRepository,
        Logger $logger,
        Config $config,
        StatusHistoryFactory $statusHistoryFactory,
        StatusHistory $statusHistoryResource,
        Order $ninjaOrder,
        TrackingNumber $trackingNumber,
        ShipmentStatus $shipmentStatusModel,
        OrderStatus $orderStatusModel,
        Data $mailSender,
        NotDelivered $notDelivered,
        ManagerInterface $eventManager,
        private \OnitsukaTiger\NetSuite\Model\SuiteTalk\UpdateShipmentStatusToNetsuite $updateShipmentStatusToNetsuite,
        private SourceRepositoryInterface $sourceRepository,
        private \OnitsukaTiger\Cegid\Model\UpdateShipmentStatusToCegid  $updateShipmentStatusToCegid,
        private ShipmentRepositoryInterface $shipmentRepository,
    ) {
        $this->httpRequest = $httpRequest;
        $this->storeRepository = $storeRepository;
        $this->logger = $logger;
        $this->config = $config;
        $this->statusHistoryFactory = $statusHistoryFactory;
        $this->statusHistoryResource = $statusHistoryResource;
        $this->ninjaOrder = $ninjaOrder;
        $this->trackingNumber = $trackingNumber;
        $this->shipmentStatusModel = $shipmentStatusModel;
        $this->orderStatusModel = $orderStatusModel;
        $this->mailSender = $mailSender;
        $this->notDelivered = $notDelivered;
        $this->eventManager = $eventManager;
    }

    /**
     * @param string $countryCode
     * @return string|void
     * @throws AlreadyExistsException
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function updateStatus($countryCode)
    {
        $content = $this->httpRequest->getContent();
        $websiteId = $this->getWebSiteIdFromCountryCode($countryCode);

        $this->verifyWebhook($content, $websiteId);

        $history = $this->getHistoryModel($content);
        $this->statusHistoryResource->save($history);
    }

    /**
     * @param string $countryCode
     * @return ResponseInterface
     * @throws AlreadyExistsException
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function complete($countryCode)
    {
        $content = $this->httpRequest->getContent();
        $websiteId = $this->getWebSiteIdFromCountryCode($countryCode);

        $this->verifyWebhook($content, $websiteId);

        $this->isTrackingIdExits($content);

        $history = $this->getHistoryModel($content);
        $this->statusHistoryResource->save($history);
        $json = json_decode($content, true);

        $this->logger->info("-----Successful Delivery from Ninja Webhook-----" . print_r($json, true));

        $shipment = $this->trackingNumber->getShipmentFromTrackingNumber($json['tracking_id']);
        if (!$shipment) {
            $this->throwWebApiException("shipment doesn't exits", 400);
        }
        $this->validateShipment($shipment, [ShipmentStatus::STATUS_SHIPPED]);

        $this->shipmentStatusModel->updateStatus($shipment, ShipmentStatus::STATUS_DELIVERED);
        $this->orderStatusModel->setOrderStatus($shipment->getOrder());
        if (in_array($shipment->getStoreId(), [8, 10])) {
            $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
            try {
                $source = $this->sourceRepository->get($sourceCode);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->logger->info(__('Assign source no longer exist.'));
            }
            if ($source && $source->getIsShippingFromStore()) {
                $shipmentRepository = $this->shipmentRepository->get($shipment->getId());
                $this->updateShipmentStatusToCegid->execute($shipmentRepository, ShipmentStatus::STATUS_DELIVERED);
            } else {
                $this->updateShipmentStatusToNetsuite->execute($shipment, ShipmentStatus::STATUS_DELIVERED);
            }
        }
        $this->eventManager->dispatch(self::EVENT_SEND_EMAIL_DELIVERED, ['shipment' => $shipment]);

        return new Response(true);
    }

    /**
     * @param string $countryCode
     * @return ResponseInterface
     * @throws AlreadyExistsException
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function return($countryCode)
    {
        $content = $this->httpRequest->getContent();
        $websiteId = $this->getWebSiteIdFromCountryCode($countryCode);

        $this->verifyWebhook($content, $websiteId);

        $this->isTrackingIdExits($content);

        $history = $this->getHistoryModel($content);
        $this->statusHistoryResource->save($history);

        $json = json_decode($content, true);
        $this->logger->info("-----Returned to Sender from Ninja Webhook-----" . print_r($json, true));

        $shipment = $this->trackingNumber->getShipmentFromTrackingNumber($json['tracking_id']);
        if (!$shipment) {
            $this->throwWebApiException("shipment doesn't exits", 400);
        }
        $this->validateShipment($shipment, [ShipmentStatus::STATUS_SHIPPED]);
        if (in_array($shipment->getStoreId(), [8, 10])) {
            $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
            try {
                $source = $this->sourceRepository->get($sourceCode);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->logger->info(__('Assign source no longer exist.'));
            }
            if ($source && $source->getIsShippingFromStore()) {
                $shipmentRepository = $this->shipmentRepository->get($shipment->getId());
                $this->updateShipmentStatusToCegid->execute($shipmentRepository, ShipmentStatus::STATUS_DELIVERY_FAILED);
            } else {
                $this->updateShipmentStatusToNetsuite->execute($shipment, ShipmentStatus::STATUS_DELIVERY_FAILED);
            }
        }
        try {
            $this->notDelivered->makeNotDeliveredRequest($shipment, $json['tracking_id']);
            // Update status
            $this->shipmentStatusModel->updateStatus($shipment, ShipmentStatus::STATUS_DELIVERY_FAILED);
            $this->orderStatusModel->setOrderStatus($shipment->getOrder());
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $this->throwWebApiException($e->getMessage(), 500);
        }

        return new Response(true);
    }

    /**
     * Get website id from country code
     * @param $code
     * @return |null
     */
    private function getWebSiteIdFromCountryCode($code)
    {
        $websiteId = null;
        $stores = $this->storeRepository->getList();
        foreach ($stores as $store) {
            $key = $store['website_id'];
            $val = $this->config->get(Config::PATH_COUNTRY_CODE, $key);
            if ($code == $val) {
                $websiteId = $key;
                break;
            }
        }
        return $websiteId;
    }

    /**
     * verify webhook data
     * @param $data
     * @param $websiteId
     * @throws \Magento\Framework\Webapi\Exception
     */
    private function verifyWebhook($data, $websiteId)
    {
        $hmacHeader = $this->httpRequest->getHeader('X-NINJAVAN-HMAC-SHA256');

        $clientSecret = $this->config->get(Config::PATH_CLIENT_SECRET, $websiteId);
        $calculated = base64_encode(
            hash_hmac('sha256', $data, $clientSecret, true)
        );
        /* if ($hmacHeader != $calculated) {
             $this->throwWebApiException('X-NINJAVAN header verification error', 400);
         }*/
    }

    /**
     * get history model
     * @param $data
     */
    private function getHistoryModel($data)
    {
        $json = json_decode($data, true);

        /* @var \OnitsukaTiger\Ninja\Model\StatusHistory $model */
        $model = $this->statusHistoryFactory->create();
        $model->setTrackingId($json['tracking_id']);
        $model->setStatus($json['status']);
        $model->setTimestamp($json['timestamp']);
        $model->setJson($data);

        return $model;
    }

    /**
     * Throw Web API exception and add it to log
     * @param $msg
     * @param $status
     * @throws \Magento\Framework\Webapi\Exception
     */
    private function throwWebApiException($msg, $status)
    {
        $exception = new \Magento\Framework\Webapi\Exception(__($msg), $status);
        $this->logger->critical($exception);
        throw $exception;
    }

    /**
     * @param $content
     * @throws \Magento\Framework\Webapi\Exception
     */
    private function isTrackingIdExits($content)
    {
        $json = json_decode($content, true);
        if (!array_key_exists('tracking_id', $json)) {
            $msg = 'could not take tracking_id : ' . $content;
            $this->logger->error($msg);
            $this->throwWebApiException($msg, 400);
        }
    }

    /**
     * @param $shipment
     * @param array $status
     * @throws \Magento\Framework\Webapi\Exception
     */
    private function validateShipment($shipment, array $status)
    {
        $ext = $shipment->getExtensionAttributes();
        if (!in_array($ext->getStatus(), $status)) {
            $this->throwWebApiException(sprintf('shipment id [%s] is not status[%s]', $shipment->getIncrementId(), implode(', ', $status)), 400);
        }
    }
}
