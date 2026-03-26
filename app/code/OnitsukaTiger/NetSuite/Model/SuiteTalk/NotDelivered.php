<?php
/** phpcs:ignoreFile */
namespace OnitsukaTiger\NetSuite\Model\SuiteTalk;

class NotDelivered extends \OnitsukaTiger\NetSuite\Model\SuiteTalk
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
        \Psr\Log\LoggerInterface $psrLogger
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
    public function execute(
        \Magento\Sales\Model\Order\Shipment $shipment,
        \Amasty\Rma\Api\Data\RequestInterface $request
    ) {
        $this->psrlogger->info("Execute");
        // check enable
        if (!$this->scopeConfig->getValue('netsuite/suitetalk/not_delivered', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $shipment->getStoreId())) {
            $this->logger->info('Not Delivered Sync sync is disable');
            $this->psrlogger->info("Scope Config value");
            return;
        }
        $dateTimeRelease = $this->rmaHelper->getGeneralConfig('date_release', $request->getStoreId());
        $order = $shipment->getOrder();
        $createdAtBasedTimeZone = $this->timezone->formatDateTime(
            $order->getCreatedAt(),
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::SHORT,
            null,
            $this->timezone->getConfigTimezone('store', $request->getStoreId())
        );
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
        $fields = $this->prepareCustomFieldRequest($shipment, $request);
        // add Custom Field List
        $salesOrder->customFieldList = $fields;

        //  generate & send soap request.
        $service = $this->getService();
        $upsertRequest = new \NetSuite\Classes\UpsertRequest();
        $upsertRequest->record = $salesOrder;
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
        return $updateResponse;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param \Amasty\Rma\Api\Data\RequestInterface $request
     * @return \NetSuite\Classes\CustomFieldList
     */
    public function prepareCustomFieldRequest(
        \Magento\Sales\Model\Order\Shipment $shipment,
        \Amasty\Rma\Api\Data\RequestInterface $request
    ) {
        $requestItems = [];
        foreach ($request->getRequestItems() as $item) {
            /** @var \Magento\Sales\Model\Order\Shipment\Item $item */
            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            $orderItem = $this->orderItemRepository->get($item->getOrderItemId());
            $orderItemConfig = $this->orderItemRepository->get($orderItem->getParentItemId());

            $itemDiscountAmount = $orderItemConfig->getDiscountAmount();
            $pointDiscountAmount = $orderItemConfig->getUsedPoint();

            $discountAmountItem = ($itemDiscountAmount/$orderItemConfig->getQtyOrdered())*$item->getQty();
            $usedPointDiscount = 0;
            if ($pointDiscountAmount > 0) {
                $usedPointDiscount = ($pointDiscountAmount/$orderItemConfig->getQtyOrdered())*$item->getQty();
            }

            if ($usedPointDiscount > 0) {
                $discountAmountItem = $discountAmountItem - $usedPointDiscount;
            }

            $items = [
                'shipping' => 0,
                'shipmentId' => $shipment->getIncrementId(),
                'qty' => (int) $item->getQty(),
                'loyalty' => $this->netSuiteFloatNumberFormat($usedPointDiscount),
                'discount' => $this->netSuiteFloatNumberFormat($discountAmountItem),
                'detailId' => $orderItem->getProductId(),
                'sku' => $orderItem->getSku(),
            ];
            $requestItems[] = $items;
        }

        // Custom Field
        $fieldReturnRequestId = new \NetSuite\Classes\StringCustomFieldRef();
        $fieldReturnRequestId->scriptId = self::SCRIPT_ID_CUSTBODY_MJ_RETURN_REQUEST_ID;
        $fieldReturnRequestId->value = $request->getRequestId();

        $fieldOrderStatus = new \NetSuite\Classes\StringCustomFieldRef();
        $fieldOrderStatus->scriptId = self::SCRIPT_ID_CUSTBODY_ORDER_STATUS;
        $fieldOrderStatus->value = 'RTO initiated';

        $fieldOrderReturnDetails = new \NetSuite\Classes\StringCustomFieldRef();
        $fieldOrderReturnDetails->scriptId = self::SCRIPT_ID_CUSTBODY_ORDER_RETURN_DETAILS;
        $fieldOrderReturnDetails->value = json_encode($requestItems);

        if (in_array($request->getStoreId(), [8, 10])) {
            $createdAtBasedTimeZone = $this->timezone->formatDateTime(
                date('Y-m-d H:i:s'),
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::SHORT,
                null,
                $this->timezone->getConfigTimezone('store', $request->getStoreId())
            );
            $date = \DateTime::createFromFormat('n/j/y, g:i A', $createdAtBasedTimeZone, new \DateTimeZone('Asia/Kolkata')); // Change timezone if needed

            $date->setTimezone(new \DateTimeZone('UTC'));
            $createdAtDate = $date->format('Y-m-d\TH:i:s.000O');
            $fieldOrderReturnDetailsDate = new \NetSuite\Classes\DateCustomFieldRef();
            $fieldOrderReturnDetailsDate->scriptId = "custbody_rto_date";
            $fieldOrderReturnDetailsDate->value = $createdAtDate;
        }

        // Custom Field List
        $fields = new \NetSuite\Classes\CustomFieldList();
        if (in_array($request->getStoreId(), [8, 10])) {
            $fields->customField = [$fieldReturnRequestId, $fieldOrderStatus, $fieldOrderReturnDetails, $fieldOrderReturnDetailsDate];
        } else {
            $fields->customField = [$fieldReturnRequestId, $fieldOrderStatus, $fieldOrderReturnDetails];
        }

        return $fields;
    }
}
