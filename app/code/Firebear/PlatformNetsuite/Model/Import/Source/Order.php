<?php
 /**
  * Copyright © Firebear Studio, Inc. All rights reserved.
  * See COPYING.txt for license details.
  */
namespace Firebear\PlatformNetsuite\Model\Import\Source;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\ImportExport\Model\ResourceModel\Helper as ResourceHelper;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\InvoiceRepository;

/**
 * Netsuite Order source
 */
class Order extends AbstractSource
{
    /**
     * Increment id prefix
     */
    const INCREMENT_ID_PREFIX = 'NETSUITE';

    /**
     * Default store id
     */
    const DEFAULT_STORE_ID = 1;

    /**
     * @var \Firebear\PlatformNetsuite\Model\Source\Gateway\Order
     */
    protected $gateway;

    /**
     * Config data
     *
     * @var array
     */
    protected $_data = [];

    /**
     * Id Of Next Entity Row
     *
     * @var array
     */
    protected $_nextEntityIds = [];

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory
     */
    protected $orderStatusCollectionFactory;

    /**
     * @var ResourceHelper
     */
    protected $resourceHelper;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var InvoiceRepository
     */
    protected $invoiceRepository;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     *
     */
    protected $productRepository;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     *
     */
    protected $customerRepository;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var array
     */
    private $orderStatuses;

    /**
     * @var array
     */
    private $orders;

    /**
     * @var array
     */
    private $orderIncrementIds;

    /**
     * @var array
     */
    private $orderItems;

    /**
     * @var array
     */
    private $orderShipments;

    /**
     * @var array
     */
    private $orderInvoices;

    /**
     * @var array
     */
    private $orderStatusData;

    /**
     * @var
     */
    private $orderData;

    /**
     * @var array
     */
    private $importOrderItemData = [];

    /**
     * @var array
     */
    private $invoiceStatus = [
        'Open' => Invoice::STATE_OPEN,
        'Paid In Full' => Invoice::STATE_PAID,
        'Voided' => Invoice::STATE_CANCELED
    ];
    private $orderItemIds = [];

    /**
     * @var array
     */
    private $orderCreditMemos = [];

    /**
     * Order constructor.
     * @param Gateway\Order $gateway
     * @param \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $orderStatusCollectionFactory
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     * @param \Magento\Framework\App\RequestInterface $request
     * @param ResourceHelper $resourceHelper
     * @param InvoiceRepository $invoiceRepository
     * @param null $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Firebear\PlatformNetsuite\Model\Import\Source\Gateway\Order $gateway,
        \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $orderStatusCollectionFactory,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        \Magento\Framework\App\RequestInterface $request,
        ResourceHelper $resourceHelper,
        InvoiceRepository $invoiceRepository,
        $data = null
    ) {
        $this->gateway = $gateway;
        $this->orderStatusCollectionFactory = $orderStatusCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->resourceHelper = $resourceHelper;
        $this->request = $request;
        $this->invoiceRepository = $invoiceRepository;
        $this->parseEntities($data);
        $this->_colNames = array_keys($this->entities[0] ?? []);
    }

    /**
     * @param $configData
     * @return array|bool|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function parseEntities($configData)
    {
        $this->entities = parent::parseEntities($configData);
        $entities = [];
        $netsuiteOrderData = [];

        foreach ($this->entities as $entity) {
            if (isset($this->orders[$entity['internalId']])) {
                continue;
            } else {
                $this->orders[$entity['internalId']] = true;
            }

            $configData['netsuite_internal_id'] = $entity['internalId'];
            if (!empty($configData['netsuite_internal_id'])) {
                $netsuiteOrderData = $this->gateway->uploadOrderData($configData);
                $netsuiteOrderData['entity_id'] = $this->getOrderEntityId(
                    $configData['netsuite_internal_id']
                );
            }

            if (!empty($netsuiteOrderData)) {
                $orderItems = $netsuiteOrderData['items'];
                $this->importOrderItemData = array_values(
                    array_column(
                        $netsuiteOrderData['items'],
                        'netsuite_internal_id'
                    )
                );
                $netsuiteOrderData['item'] = array_shift($orderItems);
                $data = $this->prepareOrderData($netsuiteOrderData);
                $entities[] = $data;
                $netsuiteOrderData['item'] = array_shift($orderItems);
                $netsuiteOrderData['entity_id'] = $data['entity_id'];
                $billingAddressData = $this->prepareBillingAddressData($netsuiteOrderData);
                $data['billing_address_entity_id'] = $billingAddressData['address:entity_id'];
                $entities[] = $billingAddressData;
                if (!empty($netsuiteOrderData['shipment'])) {
                    foreach ($netsuiteOrderData['shipment'] as $shipment) {
                        $shipmentData = $this->prepareShipmentData($shipment, $data);
                        $entities = array_merge($entities, $shipmentData);
                    }
                }
                if (!empty($netsuiteOrderData['invoice'])) {
                    $entities = $this->addInvoiceAndCreditMemoData($netsuiteOrderData['invoice'], $data, $entities);
                }
                if (!empty($netsuiteOrderData['cash_sale'])) {
                    $entities = $this->addInvoiceAndCreditMemoData($netsuiteOrderData['cash_sale'], $data, $entities);
                }

                if (!empty($orderItems)) {
                    foreach ($orderItems as $orderItem) {
                        $entities[] = $this->prepareItemData($orderItem);
                    }
                }
            }
        }
        $this->entities = $entities;
    }

    /**
     * @param $invoices
     * @param $data
     * @param $entities
     * @return array|mixed
     */
    private function addInvoiceAndCreditMemoData($invoices, $data, $entities) {
        foreach ($invoices as $invoice) {
            $invoiceData = $this->prepareInvoiceData($invoice, $data);
            $entities = array_merge($entities, $invoiceData['invoice_data']);
            foreach ($invoiceData['credit_memo_data'] as $creditMemoData) {
                $entities = array_merge($entities, $creditMemoData);
            }
        }
        return $entities;
    }

    /**
     * @param $netsuiteInternalId
     * @return int|null
     */
    private function getOrderEntityId($netsuiteInternalId)
    {
        $filter = $this->filterBuilder->setField('netsuite_internal_id')
            ->setValue($netsuiteInternalId)
            ->setConditionType('eq')
            ->create();
        $orders = (array)($this->orderRepository->getList(
            $this->searchCriteriaBuilder->addFilters([$filter])->create()
        )->getItems());

        $order = array_shift($orders);

        if (!empty($order)) {
            $this->initOrderData($order);
            return $order->getId();
        }
        return null;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    private function initOrderData($order)
    {
        $this->orderData[$order->getEntityId()] = $order->getData();
        $this->orderIncrementIds[$order->getEntityId()] = $order->getIncrementId();
        $this->orderStatusData[$order->getEntityId()] = [
            'state' => $order->getState(),
            'status' => $order->getStatus(),
        ];
        $orderItems = $order->getItems();
        $this->orderItems = [];
        foreach ($orderItems as $orderItem) {
            $this->orderItems[] = $orderItem->getData();
        }

        $shipments = $order->getShipmentsCollection();
        foreach ($shipments as $shipment) {
            $this->orderShipments[] = $shipment->getEntityId();
        }
        $invoices = $order->getInvoiceCollection();
        foreach ($invoices as $invoice) {
            $this->orderInvoices[] = $invoice->getEntityId();
        }
        $creditMemos = $order->getCreditmemosCollection();
        foreach ($creditMemos as $creditMemo) {
            $this->orderCreditMemos[] = $creditMemo->getEntityId();
        }
    }

    /**
     * @param $data
     * @return array
     */
    private function prepareOrderData($data)
    {
        $totalPaid = 0;
        $oldOrderData = [];
        if (empty($data['entity_id'])) {
            $entityId = $this->_getNextEntityId('sales_order');
        } else {
            $entityId = $data['entity_id'];
        }
        if (isset($this->orderData[$entityId])) {
            $oldOrderData = $this->orderData[$entityId];
        } else {
            $createdAt = $data['created_at'];
        }
        if (isset($data['cash_sale']) && is_array($data['cash_sale'])) {
            foreach($data['cash_sale'] as $cashSale) {
                $totalPaid = $totalPaid + $cashSale->total;
            }
        }
        if (isset($data['invoice']) && is_array($data['invoice'])) {
            foreach($data['invoice'] as $invoice) {
                $totalPaid = $totalPaid + $invoice->total;
            }
        }

        $orderData = [
            "entity_id" => $entityId,
            "shipping_description" => !empty($data['shipping_method'])
                ? $data['shipping_method']->name : 'Free shipping',
            "is_virtual" => isset($oldOrderData['is_virtual']) ?
                $oldOrderData['is_virtual'] : 0,
            "store_id" => self::DEFAULT_STORE_ID,
            "base_discount_amount" => isset($oldOrderData['base_discount_amount']) ?
                $oldOrderData['base_discount_amount'] : 0,
            "base_grand_total" => $data['total'],
            "base_shipping_amount" => !empty($data['shipping_amount'])
                ? $data['shipping_amount'] : 0,
            "base_shipping_tax_amount" => isset($oldOrderData['base_shipping_tax_amount']) ?
                $oldOrderData['base_shipping_tax_amount'] : 0,
            "base_subtotal" => $data['subtotal'],
            "base_total_paid" => $totalPaid,
            "base_tax_amount" => !empty($data['taxTotal']) ? $data['taxTotal'] : 0,
            "base_to_global_rate" => isset($oldOrderData['base_to_global_rate']) ?
                $oldOrderData['base_to_global_rate'] : 1,
            "base_to_order_rate" => isset($oldOrderData['base_to_order_rate']) ?
                $oldOrderData['base_to_order_rate'] : 1,
            "discount_amount" => isset($oldOrderData['discount_amount']) ?
                $oldOrderData['discount_amount'] : 0,
            "grand_total" => $data['total'],
            "shipping_amount" => !empty($data['shipping_amount'])
                ? $data['shipping_amount'] : 0,
            "shipping_tax_amount" => isset($oldOrderData['shipping_tax_amount']) ?
                $oldOrderData['shipping_tax_amount'] : 0,
            "store_to_base_rate" => isset($oldOrderData['store_to_base_rate']) ?
                $oldOrderData['store_to_base_rate'] : 0,
            "store_to_order_rate" => isset($oldOrderData['store_to_order_rate']) ?
                $oldOrderData['store_to_order_rate'] : 0,
            "subtotal" => $data['subtotal'],
            "total_paid" => $totalPaid,
            "tax_amount" => !empty($data['taxTotal']) ? $data['taxTotal'] : 0,
            "total_qty_ordered" => $this->getTotalQty($data['items']),
            "send_email" => isset($oldOrderData['send_email']) ?
                $oldOrderData['send_email'] : 0,
            "base_shipping_discount_amount" => isset($oldOrderData['base_shipping_discount_amount']) ?
                $oldOrderData['base_shipping_discount_amount'] : 0,
            "base_subtotal_incl_tax" => $data['total'],
            "base_total_due" => $data['total'],
            "shipping_discount_amount" => isset($oldOrderData['shipping_discount_amount']) ?
                $oldOrderData['shipping_discount_amount'] : 0,
            "subtotal_incl_tax" => $data['total'],
            "total_due" => $data['total'],
            "base_currency_code" => $data['currency'],
            "global_currency_code" => $data['currency'],
            "order_currency_code" => $data['currency'],
            "shipping_method" => !empty($data['shipping_method'])
                ? $data['shipping_method']->name . '_' . $data['shipping_method']->name
                : 'freeshipping_freeshipping',
            "store_currency_code" => $data['currency'],
            "updated_at" => $data['updated_at'],
            "total_item_count" => count($data['items']),
            "discount_tax_compensation_amount" => isset($oldOrderData['discount_tax_compensation_amount']) ?
                $oldOrderData['discount_tax_compensation_amount'] : 0,
            "base_discount_tax_compensation_amount" => isset($oldOrderData['base_discount_tax_compensation_amount']) ?
                $oldOrderData['base_discount_tax_compensation_amount'] : 0,
            "shipping_discount_tax_compensation_amount" => isset($oldOrderData['shipping_discount_tax_compensation_amount']) ?
                $oldOrderData['shipping_discount_tax_compensation_amount'] : 0,
            "base_shipping_discount_tax_compensation_amnt" => isset($oldOrderData['base_shipping_discount_tax_compensation_amnt']) ?
                $oldOrderData['base_shipping_discount_tax_compensation_amnt'] : 0,
            "shipping_incl_tax" => !empty($data['shipping_amount'])
                ? $data['shipping_amount'] : 0,
            "base_shipping_incl_tax" => !empty($data['shipping_amount'])
                ? $data['shipping_amount'] : 0,
            "netsuite_internal_id" => $data['netsuite_internal_id']
        ];
        if (isset($createdAt)) {
            $orderData['created_at'] = $createdAt;
        }
        $orderData = $this->prepareIncrementId($data, $orderData);
        $orderData = $this->prepareCustomerData($data, $orderData);
        $orderData = $this->prepareItemData($data['item'], $orderData);
        $orderData = $this->prepareShippingAddressData(
            $data['addresses']['shippingAddress'],
            $orderData
        );
        $orderData = $this->prepeareStatusData($data, $orderData);
        return $orderData;
    }

    /**
     * @param $data
     * @param $orderData
     * @return mixed
     */
    private function prepareIncrementId($data, $orderData)
    {
        if (!empty($data['entity_id'])
            && isset($this->orderIncrementIds[$data['entity_id']])
        ) {
            $incrementId = $this->orderIncrementIds[$data['entity_id']];
        } else {
            $incrementId = self::INCREMENT_ID_PREFIX . $data['netsuite_internal_id'];
        }
        $incrementIdData = ['increment_id' => $incrementId];
        $orderData = array_merge($incrementIdData, $orderData);
        return $orderData;
    }

    /**
     * @param $email
     * @param $orderData
     * @return array
     */
    private function prepareCustomerData($netsuiteOrderData, $magentoOrderData)
    {
        $data = [
            "customer_email" => $netsuiteOrderData['email'],
            "customer_is_guest" => 0,
            "customer_note_notify" => 0,
            "customer_group_id" => 1,
        ];
        $orderData = array_merge($magentoOrderData, $data);
        $customerData = $this->getCustomerData($netsuiteOrderData['customer_internal_id']);
        if (!empty($customerData)) {
            $orderData = array_merge($orderData, $customerData);
        }
        return $orderData;
    }

    /**
     * @param $netsuiteInternalId
     * @return array
     */
    private function getCustomerData($netsuiteInternalId)
    {
        $data = [];
        $filter =  $this->filterBuilder->setField('netsuite_internal_id')
            ->setValue($netsuiteInternalId)
            ->setConditionType('eq')
            ->create();
        $customers = (array)($this->customerRepository->getList(
            $this->searchCriteriaBuilder->addFilters([$filter])->create()
        )->getItems());

        $customer = array_shift($customers);

        if (!empty($customer)) {
            $data = [
                'customer_id' => $customer->getId(),
                'customer_group_id' => $customer->getGroupId(),
                "customer_firstname" => $customer->getFirstName(),
                "customer_lastname" => $customer->getLastName(),
            ];
        }
        return $data;
    }

    /**
     * @param $netsuiteOrderItems
     * @return int
     */
    private function getTotalQty($netsuiteOrderItems)
    {
        $totalQty = 0;
        foreach ($netsuiteOrderItems as $netsuiteOrderItem) {
            $totalQty += $netsuiteOrderItem['qty'];
        }
        return $totalQty;
    }

    /**
     * @param $data
     * @param $orderData
     * @return mixed
     */
    private function prepeareStatusData($data, $orderData)
    {
        $status = $data['status'];
        $magentoOrderState = 'new';
        $magentoOrderStatus = 'pending';
        $magentoOrderStatusLabel = 'Pending';
        $orderStatuses = $this->getOrderStatuses();

        if (isset($this->orderStatusData[$data['entity_id']])) {
            $magentoOrderState = $this->orderStatusData[$data['entity_id']]['state'];
            $magentoOrderStatus = $this->orderStatusData[$data['entity_id']]['status'];
            foreach ($orderStatuses as $orderStatusLabel => $orderStatus) {
                if ($orderStatus == $magentoOrderStatus) {
                    $magentoOrderStatusLabel = $orderStatusLabel;
                }
            }
        }

        if (isset($orderStatuses[$status]) && $magentoOrderState == 'new') {
            $orderData['status'] = $orderStatuses[$status];
            $orderData['state'] = 'new';
            $orderData['status_label'] = $orderStatuses[$status];
        } elseif (!empty($data['shipment']) && (!empty($data['invoice']) || !empty($data['cash_sale']))) {
            $orderData['state'] = 'complete';
            $orderData['status'] = 'complete';
            $orderData['status_label'] = 'Complete';
        } elseif ((!empty($data['shipment']) || (!empty($data['invoice']) || !empty($data['cash_sale'])))
            && $magentoOrderState == 'new') {
            $orderData['state'] = 'processing';
            $orderData['status'] = 'processing';
            $orderData['status_label'] = 'Processing';
        } else {
            $orderData['state'] = $magentoOrderState;
            $orderData['status'] = $magentoOrderStatus;
            $orderData['status_label'] = $magentoOrderStatusLabel;
        }
        return $orderData;
    }

    /**
     * @return array
     */
    private function getOrderStatuses()
    {
        if (empty($this->orderStatuses)) {
            $orderStatuses = [];
            $orderStatusesFactory = $this->orderStatusCollectionFactory->create();
            foreach ($orderStatusesFactory as $orderStatus) {
                $orderStatuses[$orderStatus->getLabel()] = $orderStatus->getStatus();
            }
            $this->orderStatuses = $orderStatuses;
        }
        return $this->orderStatuses;
    }

    /**
     * @param $item
     * @param array $data
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function prepareItemData($item, $data = [])
    {
        $productData = $this->getProductData($item['netsuite_internal_id']);
        $entityId = '';
        $orderItemData = [];
        $correctItem = false;
        $productOptions = [];
        if (isset($data['entity_id'])) {
            $entityId = $data['entity_id'];
        } elseif (isset($data['address:parent_id'])) {
            $entityId = $data['address:parent_id'];
        }

        if (!empty($this->orderItems)) {
            while (!$correctItem && count($this->orderItems)) {
                $orderItemData = array_shift($this->orderItems);
                if (!$orderItemData['parent_item_id']) {
                    $correctItem = true;
                }
            }
            $orderItemId = $orderItemData['item_id'];
            if (isset($orderItemData['product_options']['info_buyRequest'])) {
                $productOptions['info_buyRequest']['qty'] = $item['qty'];
                $productOptions = json_encode($orderItemData['product_options']);
            }
        } else {
            $orderItemId = $this->_getNextEntityId('sales_order_item');
        }
        $this->orderItemIds[$item['netsuite_internal_id']] = $orderItemId;


        $itemData = [
            "item:item_id" => $orderItemId,
            "item:order_id" => $entityId,
            "item:store_id" => self::DEFAULT_STORE_ID,
            "item:product_id" => isset($productData['id']) ? $productData['id'] : 1,
            "item:parent_item_id" => isset($orderItemData['parent_item_id']) ?
                $orderItemData['parent_item_id'] : '',
            "item:quote_item_id" => isset($orderItemData['quote_item_id']) ?
                $orderItemData['quote_item_id'] : '',
            "item:product_type" => isset($orderItemData['product_type']) ?
                $orderItemData['product_type'] : 'simple',
            "item:product_options" => $productOptions,
            "item:is_virtual" => isset($orderItemData['is_virtual']) ?
                $orderItemData['is_virtual'] : 0,
            "item:sku" => isset($productData['sku'])
                ? $productData['sku'] : $item['name'],
            "item:name" => isset($productData['name'])
                ? $productData['name'] : $item['name'],
            "item:is_qty_decimal" => isset($orderItemData['is_qty_decimal']) ?
                $orderItemData['is_qty_decimal'] : 0,
            "item:no_discount" => isset($orderItemData['no_discount']) ?
                $orderItemData['no_discount'] : 0,
            "item:qty_canceled" => isset($orderItemData['qty_canceled']) ?
                $orderItemData['qty_canceled'] : 0,
            "item:qty_invoiced" => isset($item['qty_invoiced']) ?
                $item['qty_invoiced'] : 0,
            "item:qty_ordered" => $item['qty'],
            "item:qty_refunded" => isset($orderItemData['qty_refunded']) ?
                $orderItemData['qty_refunded'] : 0,
            "item:qty_shipped" => isset($item['qty_shipped']) ?
                $item['qty_shipped'] : 0,
            "item:price" => $item['price'],
            "item:base_price" => $item['price'],
            "item:original_price" => $item['price'],
            "item:base_original_price" => $item['price'],
            "item:tax_percent" =>!empty($item['tax_percent']) ?
                $item['tax_percent'] : 0,
            "item:tax_amount" => !empty($item['tax_amount']) ?
                $item['tax_amount'] : 0,
            "item:base_tax_amount" => !empty($item['tax_amount']) ?
                $item['tax_amount'] : 0,
            "item:tax_invoiced" => isset($orderItemData['tax_invoiced']) ?
                $orderItemData['tax_invoiced'] : 0,
            "item:base_tax_invoiced" => isset($orderItemData['base_tax_invoiced']) ?
                $orderItemData['base_tax_invoiced'] : 0,
            "item:discount_percent" => isset($orderItemData['discount_percent']) ?
                $orderItemData['discount_percent'] : 0,
            "item:discount_amount" => isset($orderItemData['discount_amount']) ?
                $orderItemData['discount_amount'] : 0,
            "item:base_discount_amount" => isset($orderItemData['base_discount_amount']) ?
                $orderItemData['base_discount_amount'] : 0,
            "item:discount_invoiced" => isset($orderItemData['discount_invoiced']) ?
                $orderItemData['discount_invoiced'] : 0,
            "item:base_discount_invoiced" => isset($orderItemData['base_discount_invoiced']) ?
                $orderItemData['base_discount_invoiced'] : 0,
            "item:amount_refunded" => isset($orderItemData['amount_refunded']) ?
                $orderItemData['amount_refunded'] : 0,
            "item:base_amount_refunded" => isset($orderItemData['base_amount_refunded']) ?
                $orderItemData['base_amount_refunded'] : 0,
            "item:row_total" => isset($orderItemData['row_total']) ?
                $orderItemData['row_total'] : 0,
            "item:base_row_total" => $item['row_total'],
            "item:row_invoiced" => isset($orderItemData['row_invoiced']) ?
                $orderItemData['row_invoiced'] : 0,
            "item:base_row_invoiced" => isset($orderItemData['base_row_invoiced']) ?
                $orderItemData['base_row_invoiced'] : 0,
            "item:row_weight" => isset($orderItemData['row_weight']) ?
                $orderItemData['row_weight'] : 0,
            "item:price_incl_tax" => $item['price_incl_tax'],
            "item:base_price_incl_tax" => $item['price_incl_tax'],
            "item:row_total_incl_tax" => $item['price_incl_tax'],
            "item:base_row_total_incl_tax" => $item['price_incl_tax'],
            "item:discount_tax_compensation_amount" => isset($orderItemData['discount_tax_compensation_amount']) ?
                $orderItemData['discount_tax_compensation_amount'] : 0,
            "item:base_discount_tax_compensation_amount" => isset($orderItemData['base_discount_tax_compensation_amount']) ?
                $orderItemData['base_discount_tax_compensation_amount'] : 0,
            "item:free_shipping" => isset($orderItemData['free_shipping']) ?
                $orderItemData['free_shipping'] : 0,
            "item:is_from_ns" => true
        ];
        $data = array_merge($data, $itemData);
        return $data;
    }

    /**
     * @param $netsuiteInternalId
     * @return array
     */
    private function getProductData($netsuiteInternalId)
    {
        $data = [];
        $filter =  $this->filterBuilder->setField('netsuite_internal_id')
            ->setValue($netsuiteInternalId)
            ->setConditionType('eq')
            ->create();
        $orders = (array)($this->productRepository->getList(
            $this->searchCriteriaBuilder->addFilters([$filter])->create()
        )->getItems());

        $product = array_shift($orders);

        if (!empty($product)) {
            $data = [
                'id' => $product->getId(),
                'sku' => $product->getSku(),
                'name' => $product->getName(),
            ];
        }
        return $data;
    }

    /**
     * @param $data
     * @return mixed
     */
    private function prepareShippingAddressData($address, $data)
    {
        $addressData = $this->prepareAddressData($address, $data['entity_id']);
        $addressData = array_merge($data, $addressData);
        return $addressData;
    }

    /**
     * @param $data
     * @return mixed
     */
    private function prepareBillingAddressData($data)
    {
        $address = ['address_type' => 'billing'];
        if (!empty($data['addresses']['billingAddress'])) {
            $address = $data['addresses']['billingAddress'];
        }
        $addressData = $this->prepareAddressData($address, $data['entity_id']);
        if (isset($data['item'])) {
            $addressData = $this->prepareItemData($data['item'], $addressData);
        }
        return $addressData;
    }

    /**
     * @param $address
     * @param $orderId
     * @return array
     */
    private function prepareAddressData($address, $orderId)
    {
        $orderAddressId = $this->_getNextEntityId('sales_order_address');
        $addressData = [
            "address:entity_id" => $orderAddressId,
            "address:parent_id" => $orderId,
            "address:firstname" => isset($address['firstname'])
                ? $address['firstname'] : 'Firstname',
            "address:lastname" => isset($address['lastname'])
                ? $address['lastname'] : 'Lastname',
            "address:street" => isset($address['street'])
                ? $address['street'] : 'Street',
            "address:city" => isset($address['city'])
                ? $address['city'] : 'City',
            "address:region" => isset($address['region'])
                ? $address['region'] : 'GA',
            "address:postcode" => isset($address['postcode'])
                ? $address['postcode'] : '12345',
            "address:email" => $address['email'],
            "address:telephone" => isset($address['telephone'])
                ? $address['telephone'] : '1234567890',
            "address:country_id" => isset($address['country_id'])
                ? $address['country_id'] : 'US',
            "address:address_type" => $address['address_type']
        ];
        return $addressData;
    }

    /**
     * @param $shipment
     * @param $orderData
     * @param $shipmentOrderItems
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function prepareShipmentData($shipment, $orderData)
    {
        $entityId = $orderData['entity_id'];
        if (!empty($this->orderShipments)) {
            $shipmentEntityId = array_shift($this->orderShipments);
        } else {
            $shipmentEntityId = $this->_getNextEntityId('sales_shipment');
        }
        $fulfilmentItems = $shipment['itemList']->item;
        $fulfilQty = 0;
        $fulFilOnHand = 0;
        $fulfilQtyRemaining = 0;
        foreach ($fulfilmentItems as $item) {
            $fulfilQty = $fulfilQty + $item->quantity;
            $fulFilOnHand = $fulFilOnHand + $item->quantity;
            $fulfilQtyRemaining = $fulfilQtyRemaining + $item->quantityRemaining;
        }
        try {
            $customer = $this->customerRepository->get($orderData['customer_email']);
            $customerId = $customer->getId();
        } catch (NoSuchEntityException $exception) {
            $customerId = '';
        }
        $shipmentOrderItem = array_shift($fulfilmentItems);
        $productData = $this->getProductData($shipmentOrderItem->item->internalId);
        if (isset($this->orderItemIds[$shipmentOrderItem->item->internalId])) {
            $orderItemId = $this->orderItemIds[$shipmentOrderItem->item->internalId];
        } else {
            $orderItemId = $orderData['item:item_id'];
        }
        $shipmentData[] = [
            "shipment:entity_id" => $shipmentEntityId,
            "shipment:store_id" => self::DEFAULT_STORE_ID,
            "shipment:total_qty" => $fulfilQty,
            "shipment:order_id" => $entityId,
            "shipment:customer_id" => $customerId,
            "shipment:shipping_address_id" => $orderData['address:entity_id'],
            "shipment:billing_address_id" => $orderData['billing_address_entity_id'],
            "shipment:increment_id" => self::INCREMENT_ID_PREFIX . $shipment['internalId'],
            "shipment:created_at" => $shipment['createdDate'],
            "shipment:updated_at" => $shipment['lastModifiedDate'],
            "shipment_item:entity_id" => $this->_getNextEntityId('sales_shipment_item'),
            "shipment_item:parent_id" => $shipmentEntityId,
            "shipment_item:qty" => $shipmentOrderItem->quantity,
            "shipment_item:product_id" => (isset($productData['id'])) ? $productData['id'] : '',
            "shipment_item:order_item_id" => $orderItemId,
            "shipment_item:name" => (isset($productData['name'])) ? isset($productData['name']) : '',
            "shipment_item:sku" => (isset($productData['sku'])) ? $productData['sku'] : ''
        ];

        foreach ($fulfilmentItems as $shipmentOrderItem) {
            if (in_array($shipmentOrderItem->item->internalId, $this->orderItemIds)) {
                $orderItemId = $this->orderItemIds[$shipmentOrderItem->item->internalId];
                $productData = $this->getProductData($shipmentOrderItem->item->internalId);
                $shipmentData[] = [
                    "shipment_item:entity_id" => $this->_getNextEntityId('sales_shipment_item'),
                    "shipment_item:parent_id" => $shipmentEntityId,
                    "shipment_item:qty" => $shipmentOrderItem->quantity,
                    "shipment_item:product_id" => (isset($productData['id'])) ? $productData['id'] : '',
                    "shipment_item:order_item_id" => $orderItemId,
                    "shipment_item:name" => (isset($productData['name'])) ? isset($productData['name']) : '',
                    "shipment_item:sku" => (isset($productData['sku'])) ? $productData['sku'] : ''
                ];
            }
        }
        $package = [];
        if ($shipment['packageList']) {
            $packages = $shipment['packageList']->package;
        }
        if (!empty($packages)) {
            foreach ($packages as $package) {
                if ($package->packageTrackingNumber) {
                    $shipmentTrack[] = [
                        "shipment_track:entity_id" => $this->_getNextEntityId('sales_shipment_track'),
                        "shipment_track:parent_id" => $shipmentEntityId,
                        "shipment_track:weight" => $package->packageWeight,
                        "shipment_track:order_id" => $entityId,
                        "shipment_track:track_number" => $package->packageTrackingNumber,
                        "shipment_track:description" => $package->packageDescr,
                        "shipment_track:title" => isset($shipment['shipMethod']->name) ?
                            $shipment['shipMethod']->name : $package->packageTrackingNumber,
                        "shipment_track:carrier_code" => $shipment['carrierIdUps'],
                        "shipment_track:created_at" => $shipment['createdDate'],
                        "shipment_track:updated_at" => $shipment['lastModifiedDate']
                    ];
                }
            }
        }
        if (isset($shipmentTrack)) {
            $shipmentData = array_merge($shipmentData, $shipmentTrack);
        }
        return $shipmentData;
    }

    /**
     * @param $invoice
     * @param $orderData
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function prepareInvoiceData($invoice, $orderData)
    {
        $entityId = $orderData['entity_id'];
        if (!empty($this->orderInvoices)) {
            $invoiceEntityId = array_shift($this->orderInvoices);
        } else {
            $invoiceEntityId = $this->_getNextEntityId('sales_invoice');
        }
        $invoiceStatus = (isset($this->invoiceStatus[$invoice->status])) ?
            $this->invoiceStatus[$invoice->status] : 0;
        if (isset($invoice->currency->name)) {
            $currencyCode = $invoice->currency->name;
        } else {
            $currencyCode = $orderData['order_currency_code'];
        }
        $invoiceIncrementId = self::INCREMENT_ID_PREFIX . $invoice->internalId;
        try {
            $invoiceEntity = $this->invoiceRepository->get($invoiceEntityId);
            $invoiceItems = $invoiceEntity->getItems();
            $invoiceIncrementId = $invoiceEntity->getIncrementId();
        } catch (NoSuchEntityException $e) {
            $invoiceItems = [];
        }
        $existInvoiceItemSkuQty = [];
        foreach ($invoiceItems as &$invoiceItem) {
            $existInvoiceItemSkuQty[$invoiceItem->getSku()] = [$invoiceItem->getQty(), $invoiceItem->getEntityId()];
        }
        $invoiceItems = $invoice->itemList->item;
        $invoiceOrderItem = array_shift($invoiceItems);
        $productData = $this->getProductData($invoiceOrderItem->item->internalId);
        $invoiceData[] = [
            'invoice:entity_id' => $invoiceEntityId,
            'invoice:store_id' => self::DEFAULT_STORE_ID,
            'invoice:base_grand_total' => $invoice->total,
            'invoice:shipping_tax_amount' => $invoice->shippingTax1Rate,
            'invoice:tax_amount' => $invoice->taxTotal,
            'invoice:base_tax_amount' => $invoice->taxTotal,
            'invoice:base_shipping_tax_amount' => $invoice->taxTotal,
            'invoice:base_discount_amount' => $invoice->discountAmount,
            'invoice:grand_total' => $invoice->total,
            'invoice:shipping_amount' => $invoice->shippingCost,
            'invoice:subtotal_incl_tax' => $invoice->subTotal,
            'invoice:base_subtotal_incl_tax' => $invoice->subTotal,
            'invoice:base_shipping_amount' => $invoice->shippingCost,
            'invoice:total_qty' => $orderData['total_qty_ordered'],
            'invoice:subtotal' => $invoice->subTotal,
            'invoice:base_subtotal' => $invoice->subTotal,
            'invoice:discount_amount' => $invoice->discountAmount,
            'invoice:billing_address_id' => $orderData['billing_address_entity_id'],
            'invoice:order_id' => $entityId,
            'invoice:email_sent' => $invoice->toBeEmailed,
            'invoice:send_email' => $invoice->toBeEmailed,
            'invoice:state' => $invoiceStatus,
            'invoice:shipping_address_id' => $orderData['address:entity_id'],
            'invoice:store_currency_code' => $orderData['store_currency_code'],
            'invoice:transaction_id' => $invoice->tranId,
            'invoice:order_currency_code' => $currencyCode,
            'invoice:base_currency_code' => $currencyCode,
            'invoice:global_currency_code' => $currencyCode,
            'invoice:increment_id' => $invoiceIncrementId,
            'invoice:created_at' => $invoice->createdDate,
            'invoice:updated_at' => $invoice->lastModifiedDate,
            'invoice:shipping_incl_tax' => !empty($orderData['shipping_amount'])
                ? $orderData['shipping_amount'] : 0,
            'invoice:base_shipping_incl_tax' => !empty($orderData['shipping_amount'])
                ? $orderData['shipping_amount'] : 0
        ];
        if (isset($this->orderItemIds[$invoiceOrderItem->item->internalId])) {
            $orderItemId = $this->orderItemIds[$invoiceOrderItem->item->internalId];
            if (in_array($invoiceOrderItem->item->internalId, $this->importOrderItemData)) {
                if ($productData && isset($existInvoiceItemSkuQty[$productData['sku']])) {
                    $invoiceItemEntityId = $existInvoiceItemSkuQty[$productData['sku']][1];
                    if ($existInvoiceItemSkuQty[$productData['sku']][0] > $invoiceOrderItem->quantity) {
                        $importQuantity = $invoiceOrderItem->quantity;
                    } else {
                        $importQuantity = $existInvoiceItemSkuQty[$productData['sku']][0];
                    }
                } else {
                    $invoiceItemEntityId = $this->_getNextEntityId('sales_invoice_item');
                    $importQuantity = $invoiceOrderItem->quantity;
                }
                $invoiceData[0]['invoice_item:entity_id'] = $invoiceItemEntityId;
                $invoiceData[0]['invoice_item:parent_id'] = $invoiceEntityId;
                $invoiceData[0]['invoice_item:base_price'] = $invoiceOrderItem->rate;
                $invoiceData[0]['invoice_item:tax_amount'] = $invoiceOrderItem->tax1Amt;
                $invoiceData[0]['invoice_item:base_row_total'] = $invoiceOrderItem->amount;
                $invoiceData[0]['invoice_item:discount_amount'] = 0;
                $invoiceData[0]['invoice_item:row_total'] = $invoiceOrderItem->rate;
                $invoiceData[0]['invoice_item:base_discount_amount'] = 0;
                $invoiceData[0]['invoice_item:price_incl_tax'] = $invoiceOrderItem->grossAmt;
                $invoiceData[0]['invoice_item:base_tax_amount'] = $invoiceOrderItem->tax1Amt;
                $invoiceData[0]['invoice_item:base_price_incl_tax'] = $invoiceOrderItem->grossAmt;
                $invoiceData[0]['invoice_item:qty'] = $importQuantity;
                $invoiceData[0]['invoice_item:base_cost'] = '';
                $invoiceData[0]['invoice_item:price'] = $invoiceOrderItem->amount;
                $invoiceData[0]['invoice_item:row_total_incl_tax'] = $invoiceOrderItem->grossAmt;
                $invoiceData[0]['invoice_item:product_id'] = (isset($productData['id'])) ? $productData['id'] : '';
                $invoiceData[0]['invoice_item:order_item_id'] = $orderItemId;
                $invoiceData[0]['invoice_item:description'] = isset($invoiceOrderItem->description) ? $invoiceOrderItem->description : '';
                $invoiceData[0]['invoice_item:sku'] = (isset($productData['sku'])) ? $productData['sku'] : '';
                $invoiceData[0]['invoice_item:name'] = (isset($productData['name'])) ? $productData['name'] : '';
                $invoiceData[0]['invoice_item:discount_tax_compensation_amount'] = 0;
                $invoiceData[0]['invoice_item:base_discount_tax_compensation_amount'] = 0;
                $invoiceData[0]['invoice_item:tax_ratio'] = 0;
            }
        }

        foreach ($invoiceItems as $invoiceOrderItem) {
            if (in_array($invoiceOrderItem->item->internalId, $this->importOrderItemData)) {
                if ($this->orderItemIds[$invoiceOrderItem->item->internalId]) {
                    $orderItemId = $this->orderItemIds[$invoiceOrderItem->item->internalId];
                    $productData = $this->getProductData($invoiceOrderItem->item->internalId);
                    if ($productData && isset($existInvoiceItemSkuQty[$productData['sku']])) {
                        $invoiceItemEntityId = $existInvoiceItemSkuQty[$productData['sku']][1];
                        if ($existInvoiceItemSkuQty[$productData['sku']][0] > $invoiceOrderItem->quantity) {
                            $importQuantity = $invoiceItem->quantity;
                        } else {
                            $importQuantity = $existInvoiceItemSkuQty[$productData['sku']][0];
                        }
                    } else {
                        $invoiceItemEntityId = $this->_getNextEntityId('sales_invoice_item');
                        $importQuantity = $invoiceOrderItem->quantity;
                    }
                    $invoiceData[] = [
                        'invoice_item:entity_id' => $invoiceItemEntityId,
                        'invoice_item:parent_id' => $invoiceEntityId,
                        'invoice_item:base_price' => $invoiceOrderItem->rate,
                        'invoice_item:tax_amount' => $invoiceOrderItem->tax1Amt,
                        'invoice_item:base_row_total' => $invoiceOrderItem->amount,
                        'invoice_item:discount_amount' => 0,
                        'invoice_item:row_total' => $invoiceOrderItem->rate,
                        'invoice_item:base_discount_amount' => 0,
                        'invoice_item:price_incl_tax' => $invoiceOrderItem->grossAmt,
                        'invoice_item:base_tax_amount' => $invoiceOrderItem->tax1Amt,
                        'invoice_item:base_price_incl_tax' => $invoiceOrderItem->grossAmt,
                        'invoice_item:qty' => $importQuantity,
                        'invoice_item:base_cost' => '',
                        'invoice_item:price' => $invoiceOrderItem->amount,
                        'invoice_item:row_total_incl_tax' => $invoiceOrderItem->grossAmt,
                        'invoice_item:product_id' => (isset($productData['id'])) ? $productData['id'] : '',
                        'invoice_item:order_item_id' => $orderItemId,
                        'invoice_item:description' => isset($invoiceOrderItem->description) ? $invoiceOrderItem->description : '',
                        'invoice_item:sku' => (isset($productData['sku'])) ? $productData['sku'] : '',
                        'invoice_item:name' => (isset($productData['name'])) ? $productData['name'] : '',
                        'invoice_item:discount_tax_compensation_amount' => 0,
                        'invoice_item:base_discount_tax_compensation_amount' => 0,
                        'invoice_item:tax_ratio' => 0
                    ];
                }
            }
        }
        $creditMemo = [];
        if (isset($invoice->creditMemo)) {
            $creditMemo = $this->prepareCreditMemoData($invoice->creditMemo, $orderData, $invoiceData);
        }
        return ['invoice_data' => $invoiceData, 'credit_memo_data' => $creditMemo];
    }

    /**
     * @param $creditMemos
     * @param $orderData
     * @param $invoiceData
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function prepareCreditMemoData($creditMemos, $orderData, $invoiceData)
    {
        $creditMemoData = [];
        foreach ($creditMemos as $creditMemo) {
            $entityId = $orderData['entity_id'];
            if (!empty($this->orderCreditMemos)) {
                $creditMemoEntityId = array_shift($this->orderCreditMemos);
            } else {
                $creditMemoEntityId = $this->_getNextEntityId('sales_creditmemo');
            }
            $creditMemoOrderItems = $creditMemo->itemList->item;
            $creditMemoOrderItem = array_shift($creditMemoOrderItems);
            $productData = $this->getProductData($creditMemoOrderItem->item->internalId);
            if (isset($this->orderItemIds[$creditMemoOrderItem->item->internalId])) {
                $orderItemId = $this->orderItemIds[$creditMemoOrderItem->item->internalId];
            } else {
                $orderItemId = $orderData['item:item_id'];
            }
            $creditMemoData[] = [
                'creditmemo:entity_id' => $creditMemoEntityId,
                'creditmemo:store_id' => self::DEFAULT_STORE_ID,
                'creditmemo:adjustment_positive' => (isset($creditMemo->adjustmentQty)) ? $creditMemo->adjustmentQty : '',
                'creditmemo:base_shipping_tax_amount' => $orderData['base_shipping_tax_amount'],
                'creditmemo:store_to_order_rate' => $orderData['store_to_order_rate'],
                'creditmemo:base_discount_amount' => $creditMemo->discountTotal,
                'creditmemo:base_to_order_rate' => $orderData['base_to_order_rate'],
                'creditmemo:grand_total' => $creditMemo->total,
                'creditmemo:base_adjustment_negative' => '',
                'creditmemo:base_subtotal_incl_tax' => $creditMemo->total,
                'creditmemo:shipping_amount' => $creditMemo->shippingCost,
                'creditmemo:subtotal_incl_tax' => $creditMemo->total,
                'creditmemo:base_shipping_amount' => $creditMemo->shippingCost,
                'creditmemo:base_subtotal' => $creditMemo->subTotal,
                'creditmemo:discount_amount' => $creditMemo->discountTotal,
                'creditmemo:subtotal' => $creditMemo->subTotal,
                'creditmemo:base_grand_total' => '',
                'creditmemo:base_adjustment_positive' => '',
                'creditmemo:base_tax_amount' => '',
                'creditmemo:shipping_tax_amount' => '',
                'creditmemo:tax_amount' => $creditMemo->taxTotal,
                'creditmemo:order_id' => $entityId,
                'creditmemo:email_sent' => false,
                'creditmemo:send_email' => $creditMemo->toBeEmailed,
                'creditmemo:state' => 2,
                'creditmemo:shipping_address_id' => $orderData['address:entity_id'],
                'creditmemo:billing_address_id' => $orderData['billing_address_entity_id'],
                'creditmemo:invoice_id' => $invoiceData[0]['invoice:entity_id'],
                'creditmemo:store_currency_code' => $orderData['store_currency_code'],
                'creditmemo:order_currency_code' => $orderData['order_currency_code'],
                'creditmemo:base_currency_code' => $orderData['base_currency_code'],
                'creditmemo:global_currency_code' => $orderData['global_currency_code'],
                'creditmemo:transaction_id' => $creditMemo->tranId,
                'creditmemo:increment_id' => self::INCREMENT_ID_PREFIX . $orderData['netsuite_internal_id'],
                'creditmemo:created_at' => $creditMemo->createdDate,
                'creditmemo:updated_at' => $creditMemo->lastModifiedDate,
                'creditmemo:discount_tax_compensation_amount' => $orderData['discount_tax_compensation_amount'],
                'creditmemo:base_discount_tax_compensation_amount' => $orderData['base_discount_tax_compensation_amount'],
                'creditmemo:shipping_discount_tax_compensation_amount' => $orderData['shipping_discount_tax_compensation_amount'],
                'creditmemo:base_shipping_discount_tax_compensation_amnt' => $orderData['base_shipping_discount_tax_compensation_amnt'],
                'creditmemo:shipping_incl_tax' => $creditMemo->shippingCost,
                'creditmemo:base_shipping_incl_tax' => $creditMemo->shippingCost,
                'creditmemo_item:entity_id' => $this->_getNextEntityId('sales_creditmemo_item'),
                'creditmemo_item:parent_id' => $creditMemoEntityId,
                'creditmemo_item:base_price' => $creditMemoOrderItem->rate,
                'creditmemo_item:tax_amount' => $creditMemoOrderItem->tax1Amt,
                'creditmemo_item:base_row_total' => $creditMemoOrderItem->amount,
                'creditmemo_item:row_total' => $creditMemoOrderItem->rate,
                'creditmemo_item:price_incl_tax' => $creditMemoOrderItem->grossAmt,
                'creditmemo_item:base_tax_amount' => $creditMemoOrderItem->tax1Amt,
                'creditmemo_item:base_price_incl_tax' => $creditMemoOrderItem->grossAmt,
                'creditmemo_item:qty' => $creditMemoOrderItem->quantity,
                'creditmemo_item:base_cost' => '',
                'creditmemo_item:price' => $creditMemoOrderItem->amount,
                'creditmemo_item:row_total_incl_tax' => $creditMemoOrderItem->grossAmt,
                'creditmemo_item:product_id' => (isset($productData['id'])) ? $productData['id'] : 0,
                'creditmemo_item:order_item_id' => $orderItemId,
                'creditmemo_item:description' => $creditMemoOrderItem->description,
                'creditmemo_item:sku' => (isset($productData['sku'])) ? $productData['sku'] : '',
                'creditmemo_item:name' => (isset($productData['name'])) ? $productData['sku'] : '',
                'creditmemo_item:discount_tax_compensation_amount' => 0,
                'creditmemo_item:base_discount_tax_compensation_amount' => 0,
                'creditmemo_item:tax_ratio' => 0
            ];

            foreach ($creditMemoOrderItems as $creditMemoOrderItem) {
                if (in_array($creditMemoOrderItem->item->internalId, $this->importOrderItemData)) {
                    if (isset($this->orderItemIds[$creditMemoOrderItem->item->internalId])) {
                        $orderItemId = $this->orderItemIds[$creditMemoOrderItem->item->internalId];
                        $productData = $this->getProductData($creditMemoOrderItem->item->internalId);
                        $creditMemoData[] = [
                            'creditmemo_item:entity_id' => $this->_getNextEntityId('sales_creditmemo_item'),
                            'creditmemo_item:parent_id' => $creditMemoEntityId,
                            'creditmemo_item:base_price' => $creditMemoOrderItem->rate,
                            'creditmemo_item:tax_amount' => $creditMemoOrderItem->tax1Amt,
                            'creditmemo_item:base_row_total' => $creditMemoOrderItem->amount,
                            'creditmemo_item:row_total' => $creditMemoOrderItem->rate,
                            'creditmemo_item:price_incl_tax' => $creditMemoOrderItem->grossAmt,
                            'creditmemo_item:base_tax_amount' => $creditMemoOrderItem->tax1Amt,
                            'creditmemo_item:base_price_incl_tax' => $creditMemoOrderItem->grossAmt,
                            'creditmemo_item:qty' => $creditMemoOrderItem->quantity,
                            'creditmemo_item:base_cost' => '',
                            'creditmemo_item:price' => $creditMemoOrderItem->amount,
                            'creditmemo_item:row_total_incl_tax' => $creditMemoOrderItem->grossAmt,
                            'creditmemo_item:product_id' => (isset($productData['id'])) ? $productData['id'] : 0,
                            'creditmemo_item:order_item_id' => $orderItemId,
                            'creditmemo_item:description' => $creditMemoOrderItem->description,
                            'creditmemo_item:sku' => (isset($productData['sku'])) ? $productData['sku'] : '',
                            'creditmemo_item:name' => (isset($productData['name'])) ? $productData['sku'] : '',
                            'creditmemo_item:discount_tax_compensation_amount' => 0,
                            'creditmemo_item:base_discount_tax_compensation_amount' => 0,
                            'creditmemo_item:tax_ratio' => 0
                        ];
                    }
                }
            }
        }
        $creditMemoEntities[] = $creditMemoData;
        return $creditMemoEntities;
    }

    /**
     * @param $table
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getNextEntityId($table)
    {
        if (empty($this->_nextEntityIds[$table])) {
            $this->_nextEntityIds[$table] = $this->resourceHelper->getNextAutoincrement($table);
        }
        return $this->_nextEntityIds[$table]++;
    }
}
