<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Model\Export\Adapter\Gateway;

use Firebear\ImportExport\Logger\Logger;
use Firebear\ImportExport\Traits\General as GeneralTrait;
use Magento\Sales\Model\Order\InvoiceRepository;
use NetSuite\Classes\GetRequest;
use NetSuite\Classes\Invoice;
use NetSuite\Classes\InvoiceItem;
use NetSuite\Classes\InvoiceItemList;
use Magento\Sales\Model\Order\ShipmentRepository;
use NetSuite\Classes\InventoryItem;
use NetSuite\Classes\ItemFulfillmentItem;
use NetSuite\Classes\ItemFulfillmentShipStatus;
use Magento\Sales\Model\Order\CreditmemoRepository;
use NetSuite\Classes\AccountingPeriod;
use NetSuite\Classes\CreditMemoItem;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\SalesOrder;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Netsuite order gateway
 */
class Order extends AbstractGateway
{
    use GeneralTrait;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var ShipmentRepository
     */
    private $shipmentRepository;

    /**
     * @var CreditMemoRepository
     */
    private $creditMemoRepository;

    /**
     * @var array
     */
    protected $mappingProductIds = [];

    /**
     * Order constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     * @param CreditMemoRepository $creditMemoRepository
     * @param ShipmentRepository $shipmentRepository
     * @param InvoiceRepository $invoiceRepository
     * @param LoggerInterface $logger
     * @param ConsoleOutput $output
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        CreditMemoRepository $creditMemoRepository,
        ShipmentRepository $shipmentRepository,
        InvoiceRepository $invoiceRepository,
        Logger $logger,
        ConsoleOutput $output
    ) {
        parent::__construct($scopeConfig);
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->creditMemoRepository = $creditMemoRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->_logger = $logger;
        $this->output = $output;
    }

    /**
     * @param $entity
     * @param $offset
     * @param null $categoryId
     * @return bool|mixed
     */
    public function exportSource($data)
    {
        $fileMetadata = $this->exportOrder($data);
        if ($fileMetadata) {
            return $fileMetadata;
        } else {
            return false;
        }
    }

    /**
     * @param $data
     */
    protected function exportOrder($data)
    {
        $behaviorData = $this->getBehaviorData();
        $order = $this->orderRepository->get($data['entity_id']);
        $this->initService();
        $customerInternalId = $this->getCustomerInternalId($data);
        $orderItems = [];
        foreach ($data['items'] as $item) {
            $soi = new \NetSuite\Classes\SalesOrderItem();
            $soi->item = new \NetSuite\Classes\RecordRef();
            $soi->item->internalId = $item['internalId'];
            $soi->quantity = $item['quantity'];
            $soi->amount = $item['amount'];
            $customPriceLevel = new RecordRef();
            $customPriceLevel->internalId = -1;
            $soi->price = $customPriceLevel;
            $orderItems[] = $soi;
            $this->mappingProductIds[$item['product_id']] = $item;
        }
        if (empty($customerInternalId)) {
            $email = (!empty($data['email'])) ? $data['email'] : '';
            $errorMessage = __("Customer with email: %1 not found on the Netsuite.", $email);
            $this->addLogWriteln($errorMessage, $this->output, 'error');
            return;
        }
        if (empty($data['netsuite_internal_id'])) {
            if (empty($customerInternalId)) {
                $email = (!empty($data['email'])) ? $data['email'] : '';
                $errorMessage = __("Customer with email: %1 not found on the Netsuite.", $email);
                throw new \Magento\Framework\Exception\LocalizedException($errorMessage);
            }

            $so = new \NetSuite\Classes\SalesOrder();
            $so->entity = new \NetSuite\Classes\RecordRef();
            $so->entity->type = RecordType::customer;
            $so->entity->internalId = $customerInternalId;
            $so->itemList = new \NetSuite\Classes\SalesOrderItemList();

            if (!empty($behaviorData['order_department_internal_id'])) {
                $department = new \NetSuite\Classes\RecordRef();
                $department->internalId = $behaviorData['order_department_internal_id'];
                $so->department = $department;
            }

            if (!empty($behaviorData['order_location_internal_id'])) {
                $location = new \NetSuite\Classes\RecordRef();
                $location->internalId = $behaviorData['order_location_internal_id'];
                $so->location = $location;
            }

            if (!empty($behaviorData['customer_lead_source_internal_id'])) {
                $leadSource = new \NetSuite\Classes\RecordRef();
                $leadSource->internalId = $behaviorData['customer_lead_source_internal_id'];
                $so->leadSource = $leadSource;
            }

            if (!empty($behaviorData['sales_discount_item_internal_id'])
                && !empty($data['discount_amount'])
                && ($data['discount_amount'] !== '0.0000')) {
                $discountItem = new \NetSuite\Classes\RecordRef();
                $discountItem->internalId = $behaviorData['sales_discount_item_internal_id'];
                $so->discountItem = $discountItem;
                $so->discountRate = $data['discount_amount'];
            }

            if (!empty($data['shipping_amount'])) {
                $so->shippingCost = $data['shipping_amount'];
            }

            if (!empty($behaviorData['sales_tax_code_internal_id'])) {
                $shippingTaxCode = new \NetSuite\Classes\RecordRef();
                $shippingTaxCode->internalId = $behaviorData['sales_tax_code_internal_id'];
                $so->shippingTaxCode = $shippingTaxCode;
                $taxRate = $this->getOrderItemTaxPercent($data['items']);
                if ($taxRate) {
                    $taxItem = new \NetSuite\Classes\RecordRef();
                    $taxItem->internalId = $behaviorData['sales_tax_code_internal_id'];
                    $so->taxItem = $taxItem;
                    $so->taxRate = $taxRate;
                }
            }

            if (!empty($data['increment_id']) && $behaviorData['use_increment_id_instead_po_number']) {
                $so->otherRefNum = $data['increment_id'];
            } elseif (!empty($data['payment:po_number'])) {
                $so->otherRefNum = $data['payment:po_number'];
            }
            $shippingMethod = $order->getShippingMethod();
            if (!empty($behaviorData['netsuite_shipping_map'])) {
                foreach ($behaviorData['netsuite_shipping_map'] as $shippingMap) {
                    $method = $shippingMap['behavior_field_netsuite_shipping_map_shipping_methods'];
                    if ($method == $shippingMethod || $method . '_' . $method == $shippingMethod) {
                        $shippingMethod = new \NetSuite\Classes\RecordRef();
                        $shippingMethod->internalId = $shippingMap['behavior_field_netsuite_shipping_ns_shipping_methods'];
                        $so->shipMethod = $shippingMethod;
                    }
                }
            }
            $payment = $order->getPayment();
            $paymentMethod = $payment->getMethodInstance();
            $paymentMethodCode = $paymentMethod->getCode();
            if (!empty($behaviorData['netsuite_payment_map'])) {
                foreach ($behaviorData['netsuite_payment_map'] as $paymentMap) {
                    $method = $paymentMap['behavior_field_netsuite_payment_map_payment_methods'];
                    if ($method == $paymentMethodCode) {
                        $paymentMethod = new \NetSuite\Classes\RecordRef();
                        $paymentMethod->internalId = $paymentMap['behavior_field_netsuite_payment_ns_payment_methods'];
                        $so->paymentMethod = $paymentMethod;
                    }
                }
            }

            $customizationTypeName = \NetSuite\Classes\GetCustomizationType::transactionBodyCustomField;
            $netsuiteCustomFieldsMapping = $this->getNetsuiteCustomFieldsMapping($customizationTypeName);
            if (!empty($netsuiteCustomFieldsMapping)) {
                $customFieldList = new \NetSuite\Classes\CustomFieldList();
                foreach ($netsuiteCustomFieldsMapping as $exportAttribute => $systemAttribute) {
                    if (isset($data[$systemAttribute]) || isset($this->customFieldReplaceData[$exportAttribute])) {
                        $custentityField = new \NetSuite\Classes\StringCustomFieldRef();
                        $custentityField->scriptId = $exportAttribute;
                        $custentityField->value = isset($this->customFieldReplaceData[$exportAttribute]) ?
                            $this->customFieldReplaceData[$exportAttribute] : $data[$systemAttribute];
                        $customFieldList->customField[] = $custentityField;
                    }
                }
                if (!empty($customFieldList->customField)) {
                    $so->customFieldList = $customFieldList;
                }
            }

            $orderItems = [];
            foreach ($data['items'] as $item) {
                $soi = new \NetSuite\Classes\SalesOrderItem();
                $soi->item = new \NetSuite\Classes\RecordRef();

                if (empty($item['internalId'])) {
                    $itemSearchField = new \NetSuite\Classes\SearchStringField();
                    $itemSearchField->operator = "is";
                    $itemSearchField->searchValue = $item['sku'];
                    $search = new \NetSuite\Classes\ItemSearchBasic();
                    $search->itemId = $itemSearchField;
                    $request = new \NetSuite\Classes\SearchRequest();
                    $request->searchRecord = $search;
                    $searchResponse = $this->service->search($request);

                    if ($searchResponse->searchResult->status->isSuccess &&
                        $searchResponse->searchResult->recordList->record
                    ) {
                        $itemSearch = $searchResponse->searchResult->recordList->record[0];
                        $item['internalId'] = $itemSearch->internalId;
                    }
                }

                $soi->item->internalId = $item['internalId'];
                $soi->quantity = $item['quantity'];
                $soi->amount = $item['amount'];
                $orderItems[] = $soi;
            }
            $so->itemList->item = $orderItems;

            if (!empty($data['billing_address'])) {
                $data['billing_address']['region_code'] = $order->getBillingAddress()->getRegionCode();
                $so->billingAddress = $this->prepareAddress($data['billing_address']);
            }

            if (!empty($data['shipping_address'])) {
                $data['shipping_address']['region_code'] = $order->getShippingAddress()->getRegionCode();
                $so->shippingAddress = $this->prepareAddress($data['shipping_address']);
            }

            $request = new \NetSuite\Classes\AddRequest();
            $request->record = $so;
            $addResponse = $this->service->add($request);
            $invoices = $order->getInvoiceCollection()->getItems();
            if ($invoices &&
                in_array('sales_invoice', $behaviorData['deps']) &&
                in_array('sales_invoice_item', $behaviorData['deps'])) {
                $this->exportInvoice($invoices, $customerInternalId, $so);
            }
            if ($addResponse->writeResponse->status->isSuccess) {
                $successMessage = __(
                    'The order  %1 was successfully imported to Netsuite',
                    $data['increment_id']
                );
                $this->addLogWriteln($successMessage, $this->output);
                $internalId = $addResponse->writeResponse->baseRef->internalId;
                $order->setData('netsuite_internal_id', $internalId);
                $this->orderRepository->save($order);
                $payment = $order->getPayment();
                $paymentMethod = $payment->getMethodInstance();
                $paymentMethodCode = $paymentMethod->getCode();
                if (!empty($behaviorData['generate_customer_deposite'])) {
                    $this->createCustomerDeposite($internalId, $customerInternalId, $paymentMethodCode);
                }
                if (in_array('sales_creditmemo', $behaviorData['deps']) &&
                    in_array('sales_creditmemo', $behaviorData['deps']))
                {
                    $creditMemos = $order->getCreditMemoCollection();
                    if ($creditMemos)
                    {
                        $so->internalId = $internalId;
                        $this->exportCreditMemos($creditMemos, $customerInternalId, $so);
                    }
                }
                if (in_array('sales_shipment', $behaviorData['deps']) &&
                    in_array('sales_shipment_item', $behaviorData['deps']))
                {
                    $shipments = $order->getShipmentsCollection();
                    if ($shipments)
                    {
                        $so->internalId = $internalId;
                        $this->exportShipments($shipments, $customerInternalId, $so);
                    }
                }
            } else {
                $errorMessage = __(
                    'The Order not exported to the Netsuite.' .
                    ' Increment id: %1. Message: %2',
                    [
                        $data['increment_id'],
                        $addResponse->writeResponse->status->statusDetail[0]->message
                    ]
                );
                $this->addLogWriteln($errorMessage, $this->output, 'error');
            }
        } elseif ((in_array('sales_invoice', $behaviorData['deps']) &&
                    in_array('sales_invoice_item', $behaviorData['deps'])) ||
            (in_array('sales_shipment', $behaviorData['deps']) &&
                in_array('sales_shipment_item', $behaviorData['deps'])) ||
            (in_array('sales_creditmemo', $behaviorData['deps']) &&
                in_array('sales_creditmemo', $behaviorData['deps']))) {
            $getOrderRequest = new GetRequest();
            $getOrderRequest->baseRef = new RecordRef();
            $getOrderRequest->baseRef->type = RecordType::salesOrder;
            $getOrderRequest->baseRef->internalId = $data['netsuite_internal_id'];
            $getOrderResponse = $this->service->get($getOrderRequest);
            if ($getOrderResponse->readResponse->status->isSuccess) {
                $so = $getOrderResponse->readResponse->record;
                $invoices = $order->getInvoiceCollection()->getItems();
                $shipments = $order->getShipmentsCollection();
                $creditMemos = $order->getCreditmemosCollection();
                if ($shipments) {
                    $this->exportShipments($shipments, $customerInternalId, $so);
                }
                if ($invoices) {
                    $this->exportInvoice($invoices, $customerInternalId, $so);
                }
                if ($creditMemos)
                {
                    $this->exportCreditMemos($creditMemos, $customerInternalId, $so);
                }
            } else {
                $errorMessage = __(
                    ' Netsuite order Internal id: %1. Message: %2',
                    [
                        $data['netsuite_internal_id'],
                        $getOrderResponse->readResponse->status->statusDetail[0]->message
                    ]
                );
                $this->addLogWriteln($errorMessage, $this->output, 'error');
            }
        }
    }

    /**
     * @param $invoices
     * @param $customerInternalId
     * @param $so
     */
    protected function exportInvoice($invoices, $customerInternalId, $so)
    {
        $customerItem = new RecordRef();
        $customerItem->internalId = $customerInternalId;
        foreach ($invoices as $exportInvoice) {
            if (!$exportInvoice->getData('netsuite_internal_id')) {
                $invoiceData = $exportInvoice->getData();
                $invoiceItems = $exportInvoice->getAllItems();
                $invoice = new \NetSuite\Classes\Invoice();
                $invoice->currency = $invoiceData['order_currency_code'];
                $invoice->entity = $customerItem;
                $invoice->amountPaid = $invoiceData['grand_total'];
                $invoice->taxTotal = $invoiceData['tax_amount'];
                $invoice->shippingCost = $invoiceData['shipping_amount'];
                $invoice->billingAddress = $so->billingAddress;
                $invoice->shippingAddress = $so->shippingAddress;
                $invoice->discountRate = $invoiceData['discount_amount'];
                $invoice->itemList = new InvoiceItemList();
                $createdFromItem = new SalesOrder();
                $createdFromItem->internalId = $so->internalId;
                $invoice->createdFrom = $createdFromItem;
                $invoiceItemList = new InvoiceItemList();
                foreach ($invoiceItems as $invoiceItem) {
                    $netSuiteInvoiceItem = new InvoiceItem();
                    $netSuiteInvoiceItem->quantity = $invoiceItem->getQty();
                    $netSuiteInvoiceItem->amount = $invoiceItem->getPrice();
                    $item = new RecordRef();
                    $item->type = RecordType::inventoryItem;
                    $item->internalId = $this->mappingProductIds[$invoiceItem->getProductId()]['internalId'];
                    $netSuiteInvoiceItem->item = $item;
                    $invoiceItemList->item[] = $netSuiteInvoiceItem;
                }
                $invoice->itemList = $invoiceItemList;
                $requestToCreatedInvoice = new \NetSuite\Classes\AddRequest();
                $requestToCreatedInvoice->record = $invoice;
                $addInvoiceResponse = $this->service->add($requestToCreatedInvoice);
                if ($addInvoiceResponse->writeResponse->status->isSuccess) {
                    $invoiceInternalId = $addInvoiceResponse->writeResponse->baseRef->internalId;
                    $exportInvoice->setData('netsuite_internal_id', $invoiceInternalId);
                    $this->invoiceRepository->save($exportInvoice);
                } else {
                    $errorMessage = __(
                        ' Invoice not exported to NetSuite. Message: %1',
                        [
                            $addInvoiceResponse->writeResponse->status->statusDetail[0]->message
                        ]
                    );
                    $this->addLogWriteln($errorMessage, $this->output, 'error');
                }
            }
        }
    }

    /**
     * @param $tems
     * @return mixed|null
     */
    protected function getOrderItemTaxPercent($orderItems) {
        $taxPercent = null;
        foreach ($orderItems as $orderItem){
            if (!empty($orderItem['tax_percent']) && $orderItem['tax_percent']){
                $taxPercent = $orderItem['tax_percent'];
                break;
            }
        }
        return $taxPercent;
    }

    /**
     * @param $data
     * @return \NetSuite\Classes\Address
     */
    protected function prepareAddress($data)
    {
        $netsuiteAddress = new \NetSuite\Classes\Address();
        $netsuiteAddress->addr1 = $data['street'];
        $netsuiteAddress->addrPhone = $data['phone'];

        if (isset($this->countryMapping[$data['country']])) {
            $netsuiteAddress->country = $this->countryMapping[$data['country']];
        }

        $netsuiteAddress->city = $data['city'];
        $netsuiteAddress->state = $data['region_code'] ?: $data['state'];
        $netsuiteAddress->zip = $data['zip'];
        $netsuiteAddress->addressee = $data['addressee'];

        return $netsuiteAddress;
    }

    /**
     * @param $email
     * @return string
     */
    protected function getCustomerInternalId($data)
    {
        if (!isset($data['customer_netsuite_internal_id'])) {
            $this->service->setSearchPreferences(false, 20);
            $emailSearchField = new \NetSuite\Classes\SearchStringField();
            $emailSearchField->operator = "startsWith";
            $emailSearchField->searchValue = $data['email'];
            $search = new \NetSuite\Classes\CustomerSearchBasic();
            $search->email = $emailSearchField;
            $request = new \NetSuite\Classes\SearchRequest();
            $request->searchRecord = $search;
            $searchResponse = $this->service->search($request);

            if ($searchResponse->searchResult->status->isSuccess &&
                $searchResponse->searchResult->recordList->record
            ) {
                $customer = $searchResponse->searchResult->recordList->record[0];
                $internalId = $customer->internalId;
            } else {
                $internalId = $this->createCustomer($data);
            }
        } else {
            $internalId = $data['customer_netsuite_internal_id'];
        }
        return $internalId;
    }

    /**
     * @param $shipments
     * @param $customerInternalId
     * @param $so
     */
    protected function exportShipments($shipments, $customerInternalId, $so)
    {
        $customerItem = new RecordRef();
        $customerItem->type = RecordType::customer;
            $customerItem->internalId = $customerInternalId;
            foreach ($shipments as $exportShipments) {
                if (!$exportShipments->getData('netsuite_internal_id')) {
                    $shipmentsData = $exportShipments->getData();
                    $shipmentItems = $exportShipments->getAllItems();
                    $itemFulfilment = new \NetSuite\Classes\ItemFulfillment();
                    $itemFulfilment->shipStatus = ItemFulfillmentShipStatus::_shipped;
                    $itemFulfilment->entity = $customerItem;
                    $itemFulfilment->shippingAddress = $so->shippingAddress;
                    $itemFulfilment->shippedDate = date('c', strtotime($shipmentsData['created_at']));
                    $itemFulfilment->shipMethod = $so->shipMethod;
                    $itemFulfilment->shippingCost = $so->shippingCost;
                    $createdFromItem = new \NetSuite\Classes\SalesOrder();
                    $createdFromItem->internalId = $so->internalId;
                    $itemFulfilment->createdFrom = $createdFromItem;
                    $itemFulfilmentList = new \NetSuite\Classes\ItemFulfillmentItemList();
                    $itemFulfilmentList->replaceAll = true;
                    $line = 1;
                    foreach ($shipmentItems as $shipmentItem) {
                        $fulfilmentItem = new ItemFulfillmentItem();
                        $fulfilmentItem->location = $so->location;
                        $fulfilmentItem->department = $so->department;
                        $fulfilmentItem->quantity = $shipmentItem->getQty();
                        $item = new InventoryItem();
                        $item->internalId = $this->mappingProductIds[$shipmentItem->getProductId()]['internalId'];
                        $fulfilmentItem->item = $item;
                        $fulfilmentItem->orderLine = $line;
                        $itemFulfilmentList->item[] = $fulfilmentItem;
                        $line ++;
                    }
                    $itemFulfilment->itemList = $itemFulfilmentList;
                    $requestToCreateFulfilment = new \NetSuite\Classes\AddRequest();
                    $requestToCreateFulfilment->record = $itemFulfilment;
                    $addFulfilmentResponse = $this->service->add($requestToCreateFulfilment);
                    if ($addFulfilmentResponse->writeResponse->status->isSuccess) {
                        $itemFulfilmentInternalId = $addFulfilmentResponse->writeResponse->baseRef->internalId;
                        $exportShipments->setData('netsuite_internal_id', $itemFulfilmentInternalId);
                        $this->shipmentRepository->save($exportShipments);
                    } else {
                        $errorMessage = __(
                            'The Shipment not exported to the Netsuite.' .
                            ' Increment id: %1. Message: %2',
                            [
                                $shipmentsData['increment_id'],
                                $addFulfilmentResponse->writeResponse->status->statusDetail[0]->message
                            ]
                        );
                        $this->addLogWriteln($errorMessage, $this->output, 'error');
                    }
                }
            }
        }

    /**
     * @param $creditMemos
     * @param $customerInternalId
     * @param $so
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    protected function exportCreditMemos($creditMemos, $customerInternalId, $so)
    {
        $customerItem = new RecordRef();
        $behaviorData = $this->getBehaviorData();
        $customerItem->type = RecordType::customer;
        $customerItem->internalId = $customerInternalId;
        foreach ($creditMemos as $exportCreditMemos) {
            if (!$exportCreditMemos->getData('netsuite_internal_id')) {
                $creditMemosData = $exportCreditMemos->getData();
                $creditMemoItems = $exportCreditMemos->getAllItems();
                $nsCreditMemo = new \NetSuite\Classes\CreditMemo();
                $nsCreditMemo->shipMethod = $so->shipMethod;
                $nsCreditMemo->shippingCost = $so->shippingCost;
                $nsCreditMemo->department = $so->department;
                $nsCreditMemo->currency = $so->currency;
                $nsCreditMemo->entity = $customerItem;
                $nsCreditMemo->tranDate = date('c', strtotime($creditMemosData['created_at']));//Время создания
                $nsCreditMemo->subsidiary = $so->subsidiary;
                $nsCreditMemo->salesRep = $so->salesRep;
                $nsCreditMemo->leadSource = $so->leadSource;
                $nsCreditMemo->otherRefNum = $so->otherRefNum;
                $nsCreditMemo->amountPaid = $creditMemosData['grand_total'];
                $nsCreditMemo->isTaxable = ($creditMemosData['tax_amount']) ? 1 : 0;
                $nsCreditMemo->taxRate = $creditMemosData['tax_amount'];
                $nsCreditMemo->autoApply = $behaviorData['auto_apply_credit'];
                $nsCreditMemo->toBePrinted = $so->toBePrinted;
                $nsCreditMemo->toBeEmailed = $so->toBeEmailed;
                $nsCreditMemo->email = $so->email;
                $nsCreditMemo->toBeFaxed = $so->toBeFaxed;
                $nsCreditMemo->fax = $so->fax;
                $nsCreditMemo->billingAddress = $so->billingAddress;
                $nsCreditMemo->shippingCost = $creditMemosData['shipping_incl_tax'];
                $itemCreditMemoList = new \NetSuite\Classes\CreditMemoItemList();
                $itemCreditMemoList->replaceAll = true;
                $line = 1;
                foreach ($creditMemoItems as $creditMemoItem) {
                    $nsCreditMemoItem = new CreditMemoItem();
                    $nsCreditMemoItem->quantity = $creditMemoItem->getQty();
                    $item = new InventoryItem();
                    $item->internalId = $this->mappingProductIds[$creditMemoItem->getProductId()]['internalId'];
                    $nsCreditMemoItem->item = $item;
                    $nsCreditMemoItem->price = $creditMemoItem->getPrice();
                    $nsCreditMemoItem->quantity = $creditMemoItem->getQty();
                    $nsCreditMemoItem->amount = $nsCreditMemoItem->quantity * $nsCreditMemoItem->price;
                    $nsCreditMemoItem->quantity = $creditMemoItem->getQty();
                    $nsCreditMemoItem->orderLine = $line;
                    $itemCreditMemoList->item[] = $nsCreditMemoItem;
                    $line ++;
                }
                $nsCreditMemo->itemList = $itemCreditMemoList;
                $requestToCreateCreditMemo = new \NetSuite\Classes\AddRequest();
                $requestToCreateCreditMemo->record = $nsCreditMemo;
                $addCreditMemoResponse = $this->service->add($requestToCreateCreditMemo);
                if ($addCreditMemoResponse->writeResponse->status->isSuccess) {
                    $creditMemoInternalId = $addCreditMemoResponse->writeResponse->baseRef->internalId;
                    $exportCreditMemos->setData('netsuite_internal_id', $creditMemoInternalId);
                    $this->creditMemoRepository->save($exportCreditMemos);
                } else {
                    $errorMessage = __(
                        'The Credit Memo not exported to the Netsuite.' .
                        ' Increment id: %1. Message: %2',
                        [
                            $creditMemosData['increment_id'],
                            $addCreditMemoResponse->writeResponse->status->statusDetail[0]->message
                        ]
                    );
                    $this->addLogWriteln($errorMessage, $this->output, 'error');
                }
            }
        }
    }

    /**
     * @param $data
     * @return string
     */
    protected function createCustomer($data)
    {
        $behaviorData = $this->getBehaviorData();
        $internalId = '';
        $customer = new \NetSuite\Classes\Customer();
        $customer->lastName = $data['firstname'];
        $customer->firstName = $data['lastname'];
        $customer->phone = $data['phone'];
        $customer->email = $data['email'];
        if ($behaviorData['set_entity_id_for_customer']) {
            $customer->entityId = $data['firstname'] . ' ' . $data['lastname'];
        }

        $customFieldList =  new \NetSuite\Classes\CustomFieldList();
        $custentitySauShippingFname = new \NetSuite\Classes\StringCustomFieldRef();
        $custentitySauShippingFname->scriptId = 'custentity_sau_shipping_fname';
        $custentitySauShippingFname->value = $data['firstname'];
        $customFieldList->customField[] = $custentitySauShippingFname;
        $customer->customFieldList = $customFieldList;

        if (!empty($behaviorData['subsidiary_internal_id'])) {
            $subsidiary = new \NetSuite\Classes\RecordRef();
            $subsidiary->internalId = $behaviorData['subsidiary_internal_id'];
            $customer->subsidiary = $subsidiary;
        }

        if (!empty($behaviorData['сustomer_export_company'])) {
            $customer->companyName = (!empty($data['company'])) ?
                $data['company'] : $data['firstname'] . ' ' . $data['lastname'];
        }

        if (!empty($behaviorData['sales_rep_internal_id'])) {
            $salesRep = new \NetSuite\Classes\RecordRef();
            $salesRep->internalId = $behaviorData['sales_rep_internal_id'];
            $customer->salesRep = $salesRep;
        }

        if (!empty($behaviorData['customer_category_internal_id'])) {
            $category = new \NetSuite\Classes\RecordRef();
            $category->internalId = $behaviorData['customer_category_internal_id'];
            $customer->category = $category;
        }

        if (!empty($behaviorData['customer_terms_internal_id'])) {
            $terms = new \NetSuite\Classes\RecordRef();
            $terms->internalId = $behaviorData['customer_terms_internal_id'];
            $customer->terms = $terms;
        }

        if (!empty($behaviorData['customer_price_level_internal_id'])) {
            $priceLevel = new \NetSuite\Classes\RecordRef();
            $priceLevel->type = RecordType::priceLevel;
            $priceLevel->internalId = $behaviorData['customer_price_level_internal_id'];
            $customer->priceLevel = $priceLevel;
        }

        if (!empty($behaviorData['customer_lead_source_internal_id'])) {
            $leadSource = new \NetSuite\Classes\RecordRef();
            $leadSource->internalId = $behaviorData['customer_lead_source_internal_id'];
            $customer->leadSource = $leadSource;
        }

        $request = new \NetSuite\Classes\AddRequest();
        $request->record = $customer;
        $addResponse = $this->service->add($request);
        if ($addResponse->writeResponse->status->isSuccess) {
            $internalId = $addResponse->writeResponse->baseRef->internalId;
            if (isset($data['customer_id'])) {
                $customer = $this->customerRepository->getById($data['customer_id']);
                $customer->setCustomAttribute('netsuite_internal_id', $internalId);
                $this->customerRepository->save($customer);
            }
        }
        return $internalId;
    }

    /**
     * @param $salesOrderInternalId
     * @param $customerInternalId
     */
    protected function createCustomerDeposite($salesOrderInternalId, $customerInternalId, $paymentMethodCode)
    {
        $orderData = $this->getNetsuiteOrderData($salesOrderInternalId);
        $exportBehavior = $this->getBehaviorData();
        if (!empty($orderData['total'])) {
            $customerDeposite = new \NetSuite\Classes\CustomerDeposit();

            $salesOrder = new \NetSuite\Classes\RecordRef();
            $salesOrder->internalId = $salesOrderInternalId;
            $salesOrder->type = "salesOrder";

            $customer = new \NetSuite\Classes\RecordRef();
            $customer->internalId = $customerInternalId;
            $customer->type = "customer";

            $customerDeposite->salesOrder = $salesOrder;
            $customerDeposite->customer = $customer;
            $customerDeposite->payment = $orderData['total'];
            if (isset($exportBehavior['netsuite_payments_map_payment_methods']) &&
                isset($exportBehavior['netsuite_payments_map_bank_accounts'])) {
                foreach ($exportBehavior['netsuite_payments_map_payment_methods']['value'] as $key => $method) {
                    if ($method == $paymentMethodCode) {
                        $accountRef = new RecordRef();
                        $accountRef->type = RecordType::account;
                        $accountRef->internalId = $exportBehavior['netsuite_payments_map_bank_accounts']['value'][$key];
                        $customerDeposite->account = $accountRef;
                    }
                }
            }
            if (!$customerDeposite->account) {
                $customerDeposite->undepFunds = true;
            }

            $request = new \NetSuite\Classes\AddRequest();
            $request->record = $customerDeposite;
            $addResponse = $this->service->add($request);
            if ($addResponse->writeResponse->status->isSuccess) {
                $successMessage = __(
                    'The customer deposit for the order %1 was successfully created at Netsuite',
                    $salesOrder->internalId
                );
                $this->addLogWriteln($successMessage, $this->output);
            } else {
                $errorMessage = __(
                    'The customer deposit not created at Netsuite.' .
                    'Netsuite Sales Order Internal id: %1. Message: %2',
                    [
                        $salesOrder->internalId,
                        $addResponse->writeResponse->status->statusDetail[0]->message
                    ]
                );
                $this->addLogWriteln($errorMessage, $this->output, 'error');
            }
        }
    }

    /**
     * @param $internalId
     * @return array
     */
    private function getNetsuiteOrderData($internalId)
    {
        $orderData = [];
        $getRequest = new \NetSuite\Classes\GetRequest();
        $getRequest->baseRef = new \NetSuite\Classes\RecordRef();
        $getRequest->baseRef->internalId = $internalId;
        $getRequest->baseRef->type = "salesOrder";
        $getResponse = $this->service->get($getRequest);

        if ($getResponse->readResponse->status->isSuccess) {
            $orderData = [
                'total' => $getResponse->readResponse->record->total
            ];
        }
        return $orderData;
    }
}
