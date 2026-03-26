<?php
namespace OnitsukaTiger\NetSuite\Model\SuiteTalk;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use NetSuite\Classes\UpsertRequest;
use NetSuite\Classes\UpsertResponse;
use OnitsukaTiger\NetSuite\Model\SourceMapping;
use OnitsukaTiger\NetsuiteOrderSync\Helper\Data;

/**
 * Class ReturnOrder
 * @package OnitsukaTiger\Netsuite\Model\SuiteTalk
 */
class ReturnOrder extends \OnitsukaTiger\NetSuite\Model\SuiteTalk
{

    /**
     * @var \OnitsukaTiger\NetsuiteReturnOrderSync\Helper\Data
     */
    protected $rmaHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping
     */
    protected $storeShipping;

    /**
     * @var \Magento\Sales\Model\Order\Shipment
     */
    protected $shipmentModel;

    /**
     * ReturnOrder constructor.
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \OnitsukaTiger\Shipment\Model\ShipmentStatus $shipment
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param \OnitsukaTiger\Logger\Api\Logger $logger
     * @param SourceMapping $sourceMapping
     * @param Data $helper
     * @param \Magento\Framework\Filesystem\DirectoryList $dir
     * @param \OnitsukaTiger\NetsuiteReturnOrderSync\Helper\Data $_rmaHelper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping $storeShipping
     * @param \Magento\Sales\Model\Order\Shipment $shipmentModel
     */
    public function __construct(
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \OnitsukaTiger\Shipment\Model\ShipmentStatus $shipment,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \OnitsukaTiger\Logger\Api\Logger $logger,
        SourceMapping $sourceMapping,
        \OnitsukaTiger\NetsuiteOrderSync\Helper\Data $helper,
        \Magento\Framework\Filesystem\DirectoryList  $dir,
        \OnitsukaTiger\NetsuiteReturnOrderSync\Helper\Data $_rmaHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping $storeShipping,
        \Magento\Sales\Model\Order\Shipment $shipmentModel
    ) {
        $this->timezone = $timezone;
        $this->rmaHelper = $_rmaHelper;
        $this->storeShipping = $storeShipping;
        $this->shipmentModel = $shipmentModel;
        parent::__construct(
            $shipmentRepository,
            $shipment,
            $scopeConfig,
            $orderRepository,
            $orderItemRepository,
            $logger,
            $sourceMapping,
            $helper,
            $dir
        );
    }

    /**
     * @param $request
     * @return UpsertResponse|void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function execute($request)
    {
        $dateTimeRelease = $this->rmaHelper->getGeneralConfig('date_release', $request->getStoreId());
        $order = $this->orderRepository->get($request->getOrderId());
        $createdAtBasedTimeZone = $this->timezone->formatDateTime($order->getCreatedAt());

        $shipment = $this->shipmentModel->loadByIncrementId($request->getShipmentIncrementId());

        if (!isset($shipment) || empty($shipment->getData())) {
            $errorMessage = __(
                'The Request RMA #%1 dit not found in any Shipment',
                $request->getRequestId()
            );
            $this->logger->error($errorMessage);
            throw new InputException(__($errorMessage));
        }
        $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();

        // If ship from Store, not sync to NetSuite
        if (!$this->storeShipping->isShippingFromWareHouse($sourceCode)) {
            $successMessage = __(
                'The request RMA #%1 did not ship from Warehouse, not sync to NetSuite.',
                $request->getRequestId()
            );
            $this->logger->info($successMessage);
            return;
        }

        if (strtotime($createdAtBasedTimeZone) < strtotime($dateTimeRelease)) {
            $externalId = $this->getOrderExternalId($order, $sourceCode);
        } else {
            $externalId = $shipment->getIncrementId();
        }

        $salesOrder = $this->getReturnSalesOrderRequest($externalId, $request->getStoreId(), $sourceCode);

        return $this->handlePushToNetSuite($salesOrder, $request);
    }

    /**
     * @param $request
     * @return \NetSuite\Classes\CustomFieldList
     */
    protected function prepareCustomFieldRequest($request)
    {
        $requestItems = [];
        foreach ($request->getRequestItems() as $item) {
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
                'shipping' => "0",
                'qty' => (int) $item->getQty(),
                'loyalty' => $this->netSuiteFloatNumberFormat($usedPointDiscount),
                'discount' => $this->netSuiteFloatNumberFormat($discountAmountItem),
                'detailId' => $orderItem->getProductId(),
                'sku' => $orderItem->getSku(),
            ];

            $requestItems[] = $items;
        }

        /*load sales order by order id */
        $order = $this->orderRepository->get($request->getOrderId());

        // Custom Field
        $fieldReturnRequestId = new \NetSuite\Classes\StringCustomFieldRef();
        $fieldReturnRequestId->scriptId = self::SCRIPT_ID_CUSTBODY_MJ_RETURN_REQUEST_ID;
        $fieldReturnRequestId->value = $request->getRequestId();

        $fieldOrderStatus = new \NetSuite\Classes\StringCustomFieldRef();
        $fieldOrderStatus->scriptId = self::SCRIPT_ID_CUSTBODY_ORDER_STATUS;
        $fieldOrderStatus->value = 'Order Returned';

        $fieldOrderReturnDetails = new \NetSuite\Classes\StringCustomFieldRef();
        $fieldOrderReturnDetails->scriptId = self::SCRIPT_ID_CUSTBODY_ORDER_RETURN_DETAILS;
        $fieldOrderReturnDetails->value = json_encode($requestItems);

        if (in_array($request->getStoreId(), [8, 10])) {
            $createdAtBasedTimeZone = $this->timezone->formatDateTime(
                $request->getCreatedAt(),
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

        /*$fieldPreOrder = new \NetSuite\Classes\StringCustomFieldRef();
        $fieldPreOrder->scriptId = self::SCRIPT_ID_CUSTBODY_PRE_ORDER;
        $fieldPreOrder->value = $order->getIsPreOrder();*/

        // Custom Field List
        $fields = new \NetSuite\Classes\CustomFieldList();
        if (in_array($request->getStoreId(), [8, 10])) {
            $fields->customField = [$fieldReturnRequestId, $fieldOrderStatus, $fieldOrderReturnDetails, $fieldOrderReturnDetailsDate];
        } else {
            $fields->customField = [$fieldReturnRequestId, $fieldOrderStatus, $fieldOrderReturnDetails];
        }
        return $fields;
    }

    /**
     * @throws InputException
     */
    protected function handlePushToNetSuite(\NetSuite\Classes\SalesOrder $salesOrder, $request)
    {
        $fields = $this->prepareCustomFieldRequest($request);
        // add Custom Field List
        $salesOrder->customFieldList = $fields;

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/rmaNetsuite.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Header File Debugging Start============================');
        $logger->info('Fields: ');
        $logger->info(print_r($fields, true));
        /*$logger->info('SalesOrder: ');
        $logger->info(print_r(json_decode(json_encode($salesOrder->getData())), true));*/

        //  generate & send soap request.
        $service = $this->getService();
        $upsertRequest = new UpsertRequest();
        $upsertRequest->record = $salesOrder;
        $updateResponse = $service->upsert($upsertRequest);

        $logger->info('UpdateResponse: ');
        $logger->info(print_r(json_decode(json_encode($updateResponse)), true));

        if ($updateResponse->writeResponse->status->isSuccess) {
            $successMessage = __(
                'The Request RMA  %1 was successfully imported to Netsuite',
                $request->getRequestId()
            );
            $this->logger->info($successMessage);
        } else {
            $errorMessage = __(
                'The Request RMA not exported to the Netsuite.' .
                ' Request id: %1. Message: %2',
                [
                    $request->getRequestId(),
                    $updateResponse->writeResponse->status->statusDetail[0]->message
                ]
            );
            $this->logger->error($errorMessage);
            throw new InputException(__('The Request RMA not exported to the Netsuite'));
        }

        return $updateResponse;
    }
}
