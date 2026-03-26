<?php

namespace OnitsukaTiger\NetSuite\Model\SuiteTalk;

class Delivered extends \OnitsukaTiger\NetSuite\Model\SuiteTalk
{

    /**
     * @var \OnitsukaTiger\NetsuiteReturnOrderSync\Helper\Data
     */
    protected $rmaHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    protected $psrlogger;

    /**
     * NotDelivered constructor.
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \OnitsukaTiger\Shipment\Model\ShipmentStatus $shipment
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
     * @param \OnitsukaTiger\Logger\Api\Logger $logger
     * @param \OnitsukaTiger\NetSuite\Model\SourceMapping $sourceMapping
     * @param \OnitsukaTiger\NetsuiteOrderSync\Helper\Data $helper
     * @param \OnitsukaTiger\NetsuiteReturnOrderSync\Helper\Data $_rmaHelper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Filesystem\DirectoryList $dir
     */
    public function __construct(
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \OnitsukaTiger\Shipment\Model\ShipmentStatus $shipment,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \OnitsukaTiger\Logger\Api\Logger $logger,
        \OnitsukaTiger\NetSuite\Model\SourceMapping $sourceMapping,
        \OnitsukaTiger\NetsuiteOrderSync\Helper\Data $helper,
        \OnitsukaTiger\NetsuiteReturnOrderSync\Helper\Data $_rmaHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Filesystem\DirectoryList  $dir,
        \Psr\Log\LoggerInterface $psrLogger,
        private \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        $this->psrlogger = $psrLogger;
        $this->timezone = $timezone;
        $this->rmaHelper = $_rmaHelper;
        parent::__construct($shipmentRepository, $shipment, $scopeConfig, $orderRepository, $orderItemRepository, $logger, $sourceMapping, $helper, $dir);
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param \Amasty\Rma\Api\Data\RequestInterface $request
     * @return \NetSuite\Classes\UpsertResponse|void
     * @throws \Magento\Framework\Exception\InputException
     */
    public function execute()
    {
        $orderCollection = $this->orderFactory->create()->getCollection();
        $orderCollection->addFieldToFilter('status', 'delivered');
        $orderCollection->addFieldToFilter('store_id', ['in' => [8]]);
        $this->psrlogger->info("Execute");

        foreach ($orderCollection as $order) {
            //echo '<pre>';print_r($order->getStoreId());exit;
            // check enable
            $dateTimeRelease = $this->rmaHelper->getGeneralConfig('date_release', $order->getStoreId());
            $shipmentCollection = $order->getShipmentsCollection();

            $createdAtBasedTimeZone = $this->timezone->formatDateTime(
                $order->getCreatedAt(),
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::SHORT,
                null,
                $this->timezone->getConfigTimezone('store', $order->getStoreId())
            );
            $shipment = $shipmentCollection->getFirstItem();
            $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
            $this->psrlogger->info("Execute" . $sourceCode);
            if (strtotime($createdAtBasedTimeZone) < strtotime($dateTimeRelease)) {
                $this->psrlogger->info("Created" . $createdAtBasedTimeZone);
                $externalId = $this->getOrderExternalId($order, $sourceCode);
            } else {
                $externalId = $shipment->getIncrementId();
                $this->psrlogger->info("External ID" . $externalId);
            }

            $salesOrder = $this->getReturnSalesOrderRequest($externalId, $shipment->getStoreId(), $sourceCode);
            $fields = $this->prepareCustomFieldRequest($shipment, $order);
            // add Custom Field List
            $salesOrder->customFieldList = $fields;

            //  generate & send soap request.
            $service = $this->getService();
            $upsertRequest = new \NetSuite\Classes\UpsertRequest();
            $upsertRequest->record = $salesOrder;
            //echo '<pre>';print_r($upsertRequest);echo '</pre>';exit;
            $updateResponse = $service->upsert($upsertRequest);

            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/NetsuiteLogger.log');
            $loggerData = new \Zend_Log();
            $loggerData->addWriter($writer);

            $loggerData->info("Sales Order " . print_r(json_decode(json_encode($salesOrder)), true));
            $loggerData->info("===");
            $loggerData->info("UpsertRequest => " . print_r(json_decode(json_encode($upsertRequest)), true));
            $loggerData->info("*****************");
            $loggerData->info("Status  " . print_r(json_decode(json_encode($updateResponse->writeResponse)), true));
            $loggerData->info("===");
            $loggerData->info("Response  " . print_r(json_decode(json_encode($updateResponse->writeResponse->status->isSuccess)), true));

            $this->psrlogger->info("**********System Logger**************");
            $this->psrlogger->info("Sales Order" . print_r($salesOrder, true));
            $this->psrlogger->info("===");
            $this->psrlogger->info("Status" . print_r($updateResponse->writeResponse->status, true));
            $this->psrlogger->info("===");
            $this->psrlogger->info("Response" . print_r($updateResponse->writeResponse->status->isSuccess, true));
            $this->psrlogger->info("*****************");
            $this->psrlogger->info("UpsertRequest=> " . print_r($upsertRequest, true));
            $this->psrlogger->info("**********System Logger  End**************");

            if ($updateResponse->writeResponse->status->isSuccess) {
                $this->psrlogger->info("In");
                $successMessage = __(
                    'The Request Not Delivered RMA Shipment %1 was successfully imported to Netsuite',
                    $shipment->getIncrementId()
                );
                $updateResponse->writeResponse->baseRef->internalId;
                $this->psrlogger->info($successMessage);
            } else {
                $this->psrlogger->info("Else ***");
                $errorMessage = __(
                    'The Request Not Delivered RMA Shipment is not exported to the Netsuite.' .
                    ' Request id: %1. Message: %2',
                    [
                        $shipment->getIncrementId(),
                        $updateResponse->writeResponse->status->statusDetail[0]->message
                    ]
                );

                $this->psrlogger->error($errorMessage);
                throw new \Magento\Framework\Exception\InputException($errorMessage);
            }
            $this->psrlogger->info("Last");
            $this->psrlogger->info("Update Response" . print_r($updateResponse, true));
        }

        return $updateResponse;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param \Amasty\Rma\Api\Data\RequestInterface $request
     * @return \NetSuite\Classes\CustomFieldList
     */
    public function prepareCustomFieldRequest(
        \Magento\Sales\Model\Order\Shipment $shipment,
        \Magento\Sales\Model\Order $order
    ) {
        $fieldOrderStatus = new \NetSuite\Classes\StringCustomFieldRef();
        $fieldOrderStatus->scriptId = self::SCRIPT_ID_CUSTBODY_ORDER_STATUS;
        $fieldOrderStatus->value = 'Delivered';

        $createdAtBasedTimeZone = $this->timezone->formatDateTime(
            $order->getCreatedAt(),
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::SHORT,
            null,
            $this->timezone->getConfigTimezone('store', $order->getStoreId())
        );

        $date = \DateTime::createFromFormat('n/j/y, g:i A', $createdAtBasedTimeZone, new \DateTimeZone('Asia/Kolkata')); // Change timezone if needed

        $date->setTimezone(new \DateTimeZone('UTC'));
        $createdAtDate = $date->format('Y-m-d\TH:i:s.000O');

        $fieldOrderReturnDetails = new \NetSuite\Classes\DateCustomFieldRef();
        $fieldOrderReturnDetails->scriptId = "custbody_delivered_date";
        $fieldOrderReturnDetails->value = $createdAtDate;

        // Custom Field List
        $fields = new \NetSuite\Classes\CustomFieldList();
        $fields->customField = [$fieldOrderStatus,$fieldOrderReturnDetails];

        return $fields;
    }
}
