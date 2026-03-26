<?php

namespace OnitsukaTiger\NetSuite\Model\SuiteTalk;

use Magento\InventoryApi\Api\SourceRepositoryInterface;
use NetSuite\Classes\SalesOrder;
use NetSuite\Classes\UpdateRequest;

class UpdateShipmentStatusToNetsuite extends \OnitsukaTiger\NetSuite\Model\SuiteTalk
{
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
        private \OnitsukaTiger\NetsuiteReturnOrderSync\Helper\Data $rmaHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Filesystem\DirectoryList  $dir,
        private SourceRepositoryInterface $sourceRepository
    ) {
        $this->timezone = $timezone;
        parent::__construct($shipmentRepository, $shipment, $scopeConfig, $orderRepository, $orderItemRepository, $logger, $sourceMapping, $helper, $dir);
    }

    public function execute($shipment, $status)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/NetsuiteStatusLogger.log');
        $loggerData = new \Zend_Log();
        $loggerData->addWriter($writer);

        $loggerData->info("======= Netsuite Delivered Status - Execution Started =======");

        $shipment = $this->shipmentRepository->get($shipment->getId());
        $order = $shipment->getOrder();
        $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
        try {
            $source = $this->sourceRepository->get($sourceCode);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $loggerData->info($e->getMessage());
        }
        if ($source && $source->getIsShippingFromStore()) {
            return;
        }

        $loggerData->info("Execute Shipment ID: " . $shipment->getId());
        $dateTimeRelease = $this->rmaHelper->getGeneralConfig('date_release', $order->getStoreId());
        $createdAtBasedTimeZone = $this->timezone->formatDateTime(
            $order->getUpdatedAt(),
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::SHORT,
            null,
            $this->timezone->getConfigTimezone('store', $order->getStoreId())
        );
        $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
        if (strtotime($createdAtBasedTimeZone) < strtotime($dateTimeRelease)) {
            $loggerData->info("Created" . $createdAtBasedTimeZone);
            $externalId = $this->getOrderExternalId($order, $sourceCode);
        } else {
            $loggerData->info("Inside else shipment incrementId" . $shipment->getIncrementId());
            $externalId = $shipment->getIncrementId();
            $loggerData->info("External ID" . $externalId);
        }

        $salesOrder = new SalesOrder();
        $salesOrder->externalId = $externalId;
        $salesOrder->customForm = $this->getRecordRef(
            $this->getNetsuiteInternalIdConfig('custom_form_id', $shipment->getStoreId()),
            null,
            $this->getNetsuiteInternalIdConfig('custom_form_type', $shipment->getStoreId())
        );
        $salesOrder->entity = $this->getRecordRef(
            $this->getNetsuiteInternalIdConfig('netsuite_entity_id', $shipment->getStoreId()),
            null
        );

        $salesOrder->location = $this->getRecordRef(null, $this->sourceMapping->getNetSuiteLocation($sourceCode));
        $this->logger->debug('Service done');
        $fields = $this->prepareCustomFieldRequest($order, $status);
        // add Custom Field List
        $salesOrder->customFieldList = $fields;

        //  generate & send soap request.
        $service = $this->getService();
        $updateRequest = new UpdateRequest();
        $updateRequest->record = $salesOrder;
        $this->logger->debug('Call it.....');
        $updateResponse = $service->update($updateRequest);

        $loggerData->info("Sales Order " . print_r(json_decode(json_encode($salesOrder)), true));
        $loggerData->info("===");
        $loggerData->info("UpsertRequest => " . print_r(json_decode(json_encode($updateRequest)), true));
        $loggerData->info("*****************");
        $loggerData->info("Status  " . print_r(json_decode(json_encode($updateResponse->writeResponse)), true));
        $loggerData->info("===");
        $loggerData->info("Response  " . print_r(json_decode(json_encode($updateResponse->writeResponse->status->isSuccess)), true));

        if ($updateResponse->writeResponse->status->isSuccess) {
            $loggerData->info("Inside if updateResponse->writeResponse->status->isSuccess");
            $successMessage = __(
                'The order status Delivered was successfully updated to Netsuite',
                $order->getIncrementId()
            );
            $this->updateOrderSyncedToNetsuite($shipment);

        } else {
            $loggerData->info("Inside else updateResponse->writeResponse->status->isSuccess");
            $errorMessage = __(
                'The order status Delivered was not updated to Netsuite' .
                ' Request id: %1. Message: %2',
                [
                    $order->getIncrementId(),
                    $updateResponse->writeResponse->status->statusDetail[0]->message
                ]
            );
            $loggerData->info("Error Message: " . $errorMessage);

        }
        $loggerData->info("Update Response" . print_r($updateResponse, true));
        $loggerData->info("======= Netsuite Delivered Status - Execution End =======");
    }

    public function updateOrderSyncedToNetsuite($shipment)
    {
        $shipment->setIsOrderSyncedToNetsuite(1);
        try {
            $result = $this->shipmentRepository->save($shipment);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $result = null;
        }
        return $result;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param \Amasty\Rma\Api\Data\RequestInterface $request
     * @return \NetSuite\Classes\CustomFieldList
     */
    public function prepareCustomFieldRequest(
        \Magento\Sales\Model\Order $order,
                                   $status
    ) {
        // Custom Field
        $fieldOrderStatusUpdate = new \NetSuite\Classes\StringCustomFieldRef();
        $fieldOrderStatusUpdate->scriptId = "custbody_order_status";
        $fieldOrderStatusUpdate->value = ucfirst(strtolower($status));

        $createdAtBasedTimeZone = $this->timezone->formatDateTime(
            $order->getUpdatedAt(),
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::SHORT,
            null,
            $this->timezone->getConfigTimezone('store', $order->getStoreId())
        );
        $date = \DateTime::createFromFormat('n/j/y, g:i A', $createdAtBasedTimeZone, new \DateTimeZone('Asia/Kolkata')); // Change timezone if needed

        if ($status != 'Item Lost') {
            $date->setTimezone(new \DateTimeZone('UTC'));
            $createdAtDate = $date->format('Y-m-d\TH:i:s.000O');
            $fieldOrderStatus = new \NetSuite\Classes\DateCustomFieldRef();
            $fieldOrderStatus->scriptId = "custbody_delivered_date";
            $fieldOrderStatus->value = $createdAtDate;
        }

        // Custom Field List
        $fields = new \NetSuite\Classes\CustomFieldList();
        if ($status != 'Item Lost') {
            $fields->customField = [$fieldOrderStatusUpdate,$fieldOrderStatus];
        } else {
            $fields->customField = [$fieldOrderStatusUpdate];
        }

        return $fields;
    }
}
