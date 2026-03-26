<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\PlatformNetsuite\Plugin\Model\Import;

use Firebear\ImportExport\Model\Import;
use Firebear\ImportExport\Model\Import\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use NetSuite\Classes\SalesOrder;
use NetSuite\Classes\UpdateRequest;
use NetSuite\NetSuiteService;

/**
 * Class OrderPlugin
 * @package Firebear\PlatformNetsuite\Plugin\Model\Import
 */
class OrderPlugin
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var NetSuiteService
     */
    protected $service;

    /**
     * OrderPlugin constructor.
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param Order $model
     * @param $result
     * @return bool
     */
    public function afterImportData(
        Order $model,
        $result
    ) {
        $childrenAdapters = $model->getChildren();
        $parameters = $model->getParameters();
        $setOrderIsInNSCheckbox = isset($parameters['behavior_field_netsuite_order_is_in_m2']) &&
            $parameters['behavior_field_netsuite_order_is_in_m2'];
        $setInvoiceIsInNSCheckbox = isset($parameters['behavior_field_netsuite_invoice_is_in_m2']) &&
            $parameters['behavior_field_netsuite_invoice_is_in_m2'];
        $setShipmentIsInNSCheckbox = isset($parameters['behavior_field_netsuite_shipment_is_in_m2']) &&
            $parameters['behavior_field_netsuite_shipment_is_in_m2'];
        if ($model->getBehavior() == Import::BEHAVIOR_ADD_UPDATE &&
            $result &&
            ($setOrderIsInNSCheckbox || $setInvoiceIsInNSCheckbox || $setShipmentIsInNSCheckbox)
        ) {
            $orderIdsMap = $childrenAdapters[0]->getOrderIdsMap();
            $shipmentsIds = $childrenAdapters[3]->getShipmentIdsMap();
            $invoiceIds = $childrenAdapters[9]->getInvoiceIdsMap();
            if (!empty($orderIdsMap)) {
                $orderCollection = $this->collectionFactory->create()
                    ->addAttributeToSelect('netsuite_internal_id')
                    ->addAttributeToSelect('entity_id')
                    ->addFieldToFilter('entity_id', $orderIdsMap);
                $items = $orderCollection->getItems();
                $this->initService($parameters);
                foreach ($items as $item) {
                    $orderNetsuiteInternalId = $item->getData('netsuite_internal_id');
                    $orderId = $item->getData('entity_id');
                    $customFieldList = new \NetSuite\Classes\CustomFieldList();
                    if ($setOrderIsInNSCheckbox) {
                        $orderIsInNSCustomField = new \NetSuite\Classes\BooleanCustomFieldRef();
                        $orderIsInNSCustomField->scriptId = $parameters['behavior_field_netsuite_order_is_in_m2'];
                        $orderIsInNSCustomField->value = true;
                        $customFieldList->customField[]= $orderIsInNSCustomField;
                    }
                    if ($setInvoiceIsInNSCheckbox && isset($invoiceIds[$orderId])) {
                        $invoiceIsInNSCustomField = new \NetSuite\Classes\BooleanCustomFieldRef();
                        $invoiceIsInNSCustomField->scriptId = $parameters['behavior_field_netsuite_invoice_is_in_m2'];
                        $invoiceIsInNSCustomField->value = true;
                        $customFieldList->customField[] = $invoiceIsInNSCustomField;
                    }
                    if ($shipmentsIds && isset($shipmentsIds[$orderId])) {
                        $shipmentIsInNSCustomField = new \NetSuite\Classes\BooleanCustomFieldRef();
                        $shipmentIsInNSCustomField->scriptId = $parameters['behavior_field_netsuite_shipment_is_in_m2'];
                        $shipmentIsInNSCustomField->value = true;
                        $customFieldList->customField[] = $shipmentIsInNSCustomField;
                    }
                    $updRecord = new SalesOrder();
                    $updRecord->customFieldList = $customFieldList;
                    $updRecord->internalId = $orderNetsuiteInternalId;
                    $updateRequest = new UpdateRequest();
                    $updateRequest->record = $updRecord;
                    $updateResponse = $this->service->update($updateRequest);
                }
            }
        }
        return $result;
    }

    /**
     * @param array $config
     */
    protected function initService($config)
    {
        $config = [
            "endpoint" => $config['endpoint'],
            "host"     => $config['host'],
            "account"  => $config['account'],
            "consumerKey" => $config['consumerKey'],
            "consumerSecret" => $config['consumerSecret'],
            "token" => $config['token'],
            "tokenSecret" => $config['tokenSecret'],
            "use_old_http_protocol_version" => $config['use_old_http_protocol_version']
        ];

        $options = [
            'connection_timeout' => 6000,
            'keep_alive' => true
        ];

        if (!empty($config['use_old_http_protocol_version'])) {
            $options['stream_context'] = stream_context_create(
                ['http' => ['protocol_version' => 1.0]]
            );
        }

        $this->service = new NetSuiteService($config, $options);
    }
}
