<?php

namespace OnitsukaTiger\NetSuite\Model\SuiteTalk;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\OrderRepository;
use NetSuite\Classes\Address;
use NetSuite\Classes\InventoryItem;
use NetSuite\Classes\RecordList;
use NetSuite\Classes\TransactionSearch;
use NetSuite\Classes\WriteResponse;
use OnitsukaTiger\Logger\Api\Logger;
use OnitsukaTiger\NetSuite\Model\SourceMapping;
use OnitsukaTiger\NetsuiteOrderSync\Helper\Data;
use OnitsukaTiger\NetsuiteOrderSync\Model\Export\Adapter\Gateway\Order;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use OnitsukaTiger\Logger\StoreShipping\Logger as StoreShippingLogger;

class Invoice extends \OnitsukaTiger\NetSuite\Model\SuiteTalk
{
    const NETSUITE_TYPE_INVOICE = 'NetSuite\Classes\Invoice';

    /**
     * @var Order
     */
    protected $suiteTalk;

    /**
     * @var StoreShippingLogger
     */
    protected $storeShippingLogger;

    /**
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentStatus $shipment
     * @param ScopeConfigInterface $scopeConfig
     * @param OrderRepository $orderRepository
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param Logger $logger
     * @param SourceMapping $sourceMapping
     * @param Data $helper
     * @param \Magento\Framework\Filesystem\DirectoryList $dir
     * @param Order $suiteTalk
     * @param StoreShippingLogger $storeShippingLogger
     */
    public function __construct(
        ShipmentRepositoryInterface $shipmentRepository,
        ShipmentStatus $shipment,
        ScopeConfigInterface $scopeConfig,
        OrderRepository $orderRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        Logger $logger,
        SourceMapping $sourceMapping,
        Data $helper,
        \Magento\Framework\Filesystem\DirectoryList  $dir,
        Order $suiteTalk,
        StoreShippingLogger $storeShippingLogger
    )
    {
        $this->suiteTalk = $suiteTalk;
        $this->storeShippingLogger = $storeShippingLogger;
        parent::__construct($shipmentRepository, $shipment, $scopeConfig, $orderRepository, $orderItemRepository, $logger, $sourceMapping, $helper, $dir);
    }

    /**
     * @param $internalId
     * @param ShipmentInterface $shipment
     * @return WriteResponse
     */
    public function update($internalId, ShipmentInterface $shipment) {
        $service = $this->getService();

        $fields = new \NetSuite\Classes\CustomFieldList();
        $fields->customField = array();

        $field = new \NetSuite\Classes\StringCustomFieldRef();
        $field->scriptId = self::SCRIPT_ID_CUSTBODY_MJ_INVOICE_ID;
        $field->value = $shipment->getOrder()->getInvoiceCollection()->getFirstItem()->getEntityId();
        $fields->customField[] = $field;

        $field = new \NetSuite\Classes\StringCustomFieldRef();
        $field->scriptId = self::SCRIPT_ID_CUSTBODY_POS_CUSTOMER_NAME;
        $field->value = $shipment->getOrder()->getCustomerEmail();
        $fields->customField[] = $field;

        $field = new \NetSuite\Classes\StringCustomFieldRef();
        $field->scriptId = self::SCRIPT_ID_CUSTBODY_POS_CUSTOMER_BILLING_ADDRESS;
        $field->value = implode(",",$this->prepareAddress($shipment));
        $fields->customField[] = $field;

        $invoice = new \NetSuite\Classes\Invoice;
        $invoice->internalId = $internalId;
        $invoice->customFieldList = $fields;

        $updateRequest = new \NetSuite\Classes\UpdateRequest();
        $updateRequest->record = $invoice;
        $updateResponse = $service->update($updateRequest);

        /** @var WriteResponse $result */
        return $updateResponse->writeResponse;
    }

    /**
     * Search order by external id
     * @param $id
     * @return \NetSuite\Classes\Invoice
     * @throws Exception
     */
    public function searchByExternalId($id) {
        $service = $this->getService();

        $search = $this->getInvoiceSearch($id);

        $request = new \NetSuite\Classes\SearchRequest();
        $request->searchRecord = $search;

        /** @var \NetSuite\Classes\SearchResponse $searchResponse */
        $searchResponse = $service->search($request);
        /** @var \NetSuite\Classes\SearchResult $result */
        $result = $searchResponse->searchResult;

        if(!$result->status->isSuccess) {
            $msg = sprintf('Id: [%s]: Failed API call : %s', $id, $result->status->statusDetail[0]->message);
            $this->storeShippingLogger->error($msg);
            throw new Exception($msg);
        }
        if($result->totalRecords != 1) {
            $msg = sprintf('Id: [%s]: Failed API call : %s', $id, $result->totalRecords);
            $this->storeShippingLogger->error($msg);
            throw new Exception($msg);
        }
        /** @var \NetSuite\Classes\RecordList $list */
        $list = $result->recordList;
        /** @var \NetSuite\Classes\Invoice $invoice */
        $invoice = $list->record[0];

        return $invoice;
    }


    /**
     * Get order search
     * @param $id
     * @return TransactionSearch
     */
    protected function getInvoiceSearch($id)
    {
        $searchStringField = new \NetSuite\Classes\SearchStringField();
        $searchStringField->operator = \NetSuite\Classes\SearchStringFieldOperator::contains;
        $searchStringField->searchValue = $id;

        $searchEnumMultiSelectField = new \NetSuite\Classes\SearchEnumMultiSelectField();
        $searchEnumMultiSelectField->operator = \NetSuite\Classes\SearchEnumMultiSelectFieldOperator::anyOf;
        $searchEnumMultiSelectField->searchValue = "_invoice";

        $transactionSearchBasic = new \NetSuite\Classes\TransactionSearchBasic();
        $transactionSearchBasic->type = $searchEnumMultiSelectField;
        $transactionSearchBasic->tranId = $searchStringField;

        $transactionSearch = new TransactionSearch();
        $transactionSearch->basic = $transactionSearchBasic;

        return $transactionSearch;
    }

    /**
     * @param ShipmentInterface $shipment
     * @return array
     */
    protected function prepareAddress(ShipmentInterface $shipment)
    {
        $country = '';  $addr2 = '';
        $billingAddress = $shipment->getOrder()->getBillingAddress();
        $streets = str_replace (array("\r\n", "\n", "\r"), "\n", $billingAddress->getStreet());
        if(count($streets) > 1) {
            $addr2 = $streets[1];
        }
        if (isset($this->suiteTalk->countryMapping[$billingAddress->getCountryId()])) {
            $country = $this->suiteTalk->countryMapping[$billingAddress->getCountryId()];
        }

        return [
            'country' => $country,
            'addrPhone' => $billingAddress->getTelephone(),
            'addr1'   => $streets[0],
            'addr2' => $addr2,
            'city' =>   $billingAddress->getCity(),
            'state' =>  $billingAddress->getRegion(),
            'zip' => $billingAddress->getPostcode()
        ];
    }

    /**
     * @param $orderId
     * @param $locationCode
     * @return TransactionSearch
     */
    public function getOrderSearchByLocationCode($orderId, $locationCode)
    {
        $transactionSearchBasic = new \NetSuite\Classes\TransactionSearchBasic();

        $domain = new \NetSuite\Classes\SearchStringCustomField();
        $domain->scriptId = self::SCRIPT_ID_CUSTBODY_MJ_ORDER_ID;
        $domain->searchValue = $orderId;
        $domain->operator = 'is';

        $scfl = new \NetSuite\Classes\SearchCustomFieldList();
        $scfl->customField = array($domain);

        $domain = new \NetSuite\Classes\SearchStringCustomField();
        $domain->scriptId = self::SCRIPT_ID_CUSTBODY_MJ_LOCATION_CODE;
        $domain->searchValue = $locationCode;
        $domain->operator = 'is';

        $scfl->customField[] = $domain;
        $transactionSearchBasic->customFieldList = $scfl;

        $transactionSearch = new TransactionSearch();
        $transactionSearch->basic = $transactionSearchBasic;
        return $transactionSearch;
    }

    /**
     * @param $orderId
     * @param $locationCode
     * @return null | string
     */
    public function searchInternalIdInOrderData($orderId, $locationCode)
    {
        $service = $this->getService();

        $search = $this->getOrderSearchByLocationCode($orderId, $locationCode);

        $request = new \NetSuite\Classes\SearchRequest();
        $request->searchRecord = $search;

        /** @var \NetSuite\Classes\SearchResponse $searchResponse */
        $searchResponse = $service->search($request);
        /** @var \NetSuite\Classes\SearchResult $result */
        $result = $searchResponse->searchResult;

        if($result->totalRecords == 0) {
            return null;
        }

        if(!$result->status->isSuccess) {
            return null;
        }

        /** @var RecordList $list */
        $list = $result->recordList;
        /** @var InventoryItem $item */
        $item = $list->record[0];

        if(get_class($list->record[0]) != self::NETSUITE_TYPE_INVOICE) {
            return null;
        }

        return $item->internalId;
    }
}
