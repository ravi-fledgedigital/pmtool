<?php
/**
 * Copyright © Firebear Studio, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Firebear\PlatformNetsuite\Model\Import\Source\Gateway;

use NetSuite\Classes\CashSale;
use NetSuite\Classes\GetRequest;
use NetSuite\Classes\ItemFulfillment;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\SearchEnumMultiSelectField;
use NetSuite\Classes\SearchMultiSelectField;
use NetSuite\Classes\TransactionSearchBasic;

/**
 * Netsuite order gateway
 */
class Order extends AbstractGateway
{

    /**
     * @param $offset
     * @param null $categoryId
     * @return bool|array
     */
    public function uploadOrderData($config = null)
    {
        $orderData = [];
        $netsuiteInternalId = $config['netsuite_internal_id'];
        $this->initService($config);
        $getRequest = new \NetSuite\Classes\GetRequest();
        $getRequest->baseRef = new \NetSuite\Classes\RecordRef();
        $getRequest->baseRef->internalId = $netsuiteInternalId;
        $getRequest->baseRef->type = "salesOrder";
        $getResponse = $this->service->get($getRequest);
        $cashSaleData = $this->_getCashSaleData($netsuiteInternalId);

        if ($getResponse->readResponse->status->isSuccess) {
            $orderData = [
                'customer_internal_id' =>
                    $getResponse->readResponse->record->entity->internalId,
                'email' => trim(ltrim($getResponse->readResponse->record->email, ';')),
                'addresses' => $this->_getAddressData($getResponse),
                'items' => $this->_getProductsItemData(
                    $getResponse->readResponse->record->itemList
                ),
                'subtotal' => $getResponse->readResponse->record->subTotal,
                'total' => $getResponse->readResponse->record->total,
                'taxTotal' => $getResponse->readResponse->record->taxTotal,
                'status' => $getResponse->readResponse->record->status,
                'netsuite_internal_id' => $netsuiteInternalId,
                'shipment' => $this->_getItemFulfilmentData($netsuiteInternalId),
                'invoice' => $this->_getInvoiceData($netsuiteInternalId),
                'cash_sale' => $cashSaleData,
                'currency' => $getResponse->readResponse->record->currencyName,
                'shipping_method' => $getResponse->readResponse->record->shipMethod,
                'shipping_amount' => $getResponse->readResponse->record->shippingCost,
                'created_at' => $getResponse->readResponse->record->createdDate,
                'updated_at' => $getResponse->readResponse->record->lastModifiedDate
            ];
        }
        return $orderData;
    }

    /**
     * @param $netsuiteInternalId
     * @return array
     */
    private function _getCashRefundData($netsuiteInternalId)
    {
        $search = new \NetSuite\Classes\TransactionSearchBasic();
        $type = new \NetSuite\Classes\SearchEnumMultiSelectField();
        $type->searchValue = ['_cashRefund', '_returnAuthorization'];
        $type->operator = 'anyOf';
        $cashSale = new \NetSuite\Classes\CashSale();
        $cashSale->internalId = $netsuiteInternalId;
        $createdFrom = new \NetSuite\Classes\SearchMultiSelectField();
        $createdFrom->searchValue = $cashSale;
        $createdFrom->operator = 'anyOf';
        $search->type = $type;
        $search->createdFrom = $createdFrom;
        $request = new \NetSuite\Classes\SearchRequest();
        $request->searchRecord = $search;
        $searchResponse = $this->service->search($request);
        $cashRefundData = [];
        if ($searchResponse->searchResult->status->isSuccess
            && $searchResponse->searchResult->recordList->record
        ) {
            foreach ($searchResponse->searchResult->recordList->record as $record) {
                if ($record instanceof \Netsuite\Classes\ReturnAuthorization) {
                    $type->searchValue = ['_cashRefund'];
                    $returnAuthorization = new \NetSuite\Classes\ReturnAuthorization();
                    $returnAuthorization->internalId = $record->internalId;
                    $createdFrom->searchValue = $returnAuthorization;
                    $createdFrom->operator = 'anyOf';
                    $searchResponse = $this->service->search($request);
                    if ($searchResponse->searchResult->status->isSuccess
                        && $searchResponse->searchResult->recordList->record
                    ) {
                        $record = $searchResponse->searchResult->recordList->record[0];
                    } else {
                        continue;
                    }
                }
                $getRequest = new \NetSuite\Classes\GetRequest();
                $getRequest->baseRef = new \NetSuite\Classes\RecordRef();
                $getRequest->baseRef->internalId = $record->internalId;
                $getRequest->baseRef->type = "cashRefund";
                $getResponse = $this->service->get($getRequest);
                if ($getResponse->readResponse->status->isSuccess) {
                    $cashRefundRecord = $getResponse->readResponse->record;
                    $cashRefundData[] = $cashRefundRecord;
                }
            }
        }
        return $cashRefundData;
    }

    /**
     * @param $netsuiteInternalId
     * @return array
     */
    private function _getCreditMemoData($netsuiteInternalId)
    {
        $search = new \NetSuite\Classes\TransactionSearchBasic();
        $type = new \NetSuite\Classes\SearchEnumMultiSelectField();
        $type->searchValue = ['_creditMemo'];
        $type->operator = 'anyOf';
        $creditMemo = new \NetSuite\Classes\CreditMemo();
        $creditMemo->internalId = $netsuiteInternalId;
        $createdFrom = new \NetSuite\Classes\SearchMultiSelectField();
        $createdFrom->searchValue = $creditMemo;
        $createdFrom->operator = 'anyOf';
        $search->type = $type;
        $search->createdFrom = $createdFrom;
        $request = new \NetSuite\Classes\SearchRequest();
        $request->searchRecord = $search;
        $searchResponse = $this->service->search($request);
        $cashRefundData = [];
        if ($searchResponse->searchResult->status->isSuccess
            && $searchResponse->searchResult->recordList->record
        ) {
            foreach ($searchResponse->searchResult->recordList->record as $record) {
                $getRequest = new \NetSuite\Classes\GetRequest();
                $getRequest->baseRef = new \NetSuite\Classes\RecordRef();
                $getRequest->baseRef->internalId = $record->internalId;
                $getRequest->baseRef->type = RecordType::creditMemo;
                $getResponse = $this->service->get($getRequest);
                if ($getResponse->readResponse->status->isSuccess) {
                    $cashRefundRecord = $getResponse->readResponse->record;
                    $cashRefundData[] = $cashRefundRecord;
                }
            }
        }
        return $cashRefundData;
    }

    /**
     * @param $response
     * @return array
     */
    private function _getAddressData($response)
    {
        $addressList = ['billingAddress', 'shippingAddress'];
        $addressData = [];
        foreach ($addressList as $addressType) {
            $address = $response->readResponse->record->$addressType;
            if ($address) {
                $addressData[$addressType] = [
                    'email' => $response->readResponse->record->email,
                    'region' => $address->state,
                    'postcode' => $address->zip,
                    'firstname' => $address->addressee,
                    'lastname' => '',
                    'street' => $address->addr1,
                    'city' => $address->city,
                    'telephone' => $address->addrPhone,
                    'country_id' =>(isset($this->countryMapping[$address->country]))
                        ? $this->countryMapping[$address->country] : '',
                    'address_type' => ($addressType == 'billingAddress') ?
                        'billing':'shipping'
                ];
            } else {
                $addressData[$addressType] = [
                    'email' => $response->readResponse->record->email,
                    'region' => 'CA',
                    'postcode' => '12345',
                    'firstname' => 'Default',
                    'lastname' => 'Default',
                    'street' => 'default',
                    'city' => 'default',
                    'telephone' => '1234567890',
                    'country_id' => 'US',
                    'address_type' => ($addressType == 'billingAddress') ?
                        'billing':'shipping'
                ];
            }
        }
        return $addressData;
    }

    /**
     * @param $itemList
     * @return array
     */
    private function _getProductsItemData($itemList)
    {
        $itemData = [];
        foreach ($itemList->item as $item) {
            $itemData[] = [
                'netsuite_internal_id' => $item->item->internalId,
                'name' => $item->item->name,
                'qty' => ($item->quantity > 0) ? $item->quantity : 1,
                'price' => $item->rate,
                'tax_amount' => $item->tax1Amt,
                'tax_percent' => $item->taxRate1,
                'price_incl_tax' => $item->grossAmt,
                'row_total' => $item->amount,
                'qty_invoiced' => $item->quantityBilled,
                'qty_shipped' => $item->quantityFulfilled
            ];
        }
        return $itemData;
    }

    /**
     * @param $netsuiteInternalId
     * @return array
     */
    private function _getItemFulfilmentData($netsuiteInternalId)
    {
        $search = new \NetSuite\Classes\TransactionSearchBasic();
        $type = new \NetSuite\Classes\SearchEnumMultiSelectField();
        $type->searchValue = ['_itemFulfillment'];
        $type->operator = 'anyOf';
        $salesOrder = new \NetSuite\Classes\SalesOrder();
        $salesOrder->internalId = $netsuiteInternalId;
        $createdFrom = new \NetSuite\Classes\SearchMultiSelectField();
        $createdFrom->searchValue = $salesOrder;
        $createdFrom->operator = 'anyOf';
        $search->type = $type;
        $search->createdFrom = $createdFrom;

        $request = new \NetSuite\Classes\SearchRequest();
        $request->searchRecord = $search;
        $searchResponse = $this->service->search($request);
        $shipmentData = [];
        if ($searchResponse->searchResult->status->isSuccess
            && $searchResponse->searchResult->recordList->record
        ) {
            foreach ($searchResponse->searchResult->recordList->record as $record) {
                $getRequest = new \NetSuite\Classes\GetRequest();
                $getRequest->baseRef = new \NetSuite\Classes\RecordRef();
                $getRequest->baseRef->internalId = $record->internalId;
                $getRequest->baseRef->type = "itemFulfillment";
                $getResponse = $this->service->get($getRequest);
                if ($getResponse->readResponse->status->isSuccess) {
                    $shipmentData[] = (array)$getResponse->readResponse->record;
                }
            }
        }
        return $shipmentData;
    }

    /**
     * @param $netsuiteInternalId
     * @return array
     */
    private function _getCashSaleData($netsuiteInternalId)
    {
        $search = new \NetSuite\Classes\TransactionSearchBasic();
        $type = new \NetSuite\Classes\SearchEnumMultiSelectField();
        $type->searchValue = ['_cashSale'];
        $type->operator = 'anyOf';
        $salesOrder = new \NetSuite\Classes\SalesOrder();
        $salesOrder->internalId = $netsuiteInternalId;
        $createdFrom = new \NetSuite\Classes\SearchMultiSelectField();
        $createdFrom->searchValue = $salesOrder;
        $createdFrom->operator = 'anyOf';
        $search->type = $type;
        $search->createdFrom = $createdFrom;
        $request = new \NetSuite\Classes\SearchRequest();
        $request->searchRecord = $search;
        $searchResponse = $this->service->search($request);
        $cashSaleData = [];
        if ($searchResponse->searchResult->status->isSuccess
            && $searchResponse->searchResult->recordList->record
        ) {
            foreach ($searchResponse->searchResult->recordList->record as $record) {
                $getRequest = new \NetSuite\Classes\GetRequest();
                $getRequest->baseRef = new \NetSuite\Classes\RecordRef();
                $getRequest->baseRef->internalId = $record->internalId;
                $getRequest->baseRef->type = RecordType::cashSale;
                $getResponse = $this->service->get($getRequest);
                if ($getResponse->readResponse->status->isSuccess) {
                    $getResponse->readResponse->record->creditMemo =
                        $this->_getCashRefundData($getResponse->readResponse->record->internalId);
                    $cashSaleData[] = $getResponse->readResponse->record;
                }
            }
        }
        return $cashSaleData;
    }

    /**
     * @param $netsuiteInternalId
     * @return array
     */
    private function _getInvoiceData($netsuiteInternalId)
    {
        $search = new \NetSuite\Classes\TransactionSearchBasic();
        $type = new \NetSuite\Classes\SearchEnumMultiSelectField();
        $type->searchValue = ['_invoice'];
        $type->operator = 'anyOf';
        $salesOrder = new \NetSuite\Classes\SalesOrder();
        $salesOrder->internalId = $netsuiteInternalId;
        $createdFrom = new \NetSuite\Classes\SearchMultiSelectField();
        $createdFrom->searchValue = $salesOrder;
        $createdFrom->operator = 'anyOf';
        $search->type = $type;
        $search->createdFrom = $createdFrom;

        $request = new \NetSuite\Classes\SearchRequest();
        $request->searchRecord = $search;
        $searchResponse = $this->service->search($request);
        $invoiceData = [];
        if ($searchResponse->searchResult->status->isSuccess
            && $searchResponse->searchResult->recordList->record
        ) {
            foreach ($searchResponse->searchResult->recordList->record as $record) {
                $getRequest = new \NetSuite\Classes\GetRequest();
                $getRequest->baseRef = new \NetSuite\Classes\RecordRef();
                $getRequest->baseRef->internalId = $record->internalId;
                $getRequest->baseRef->type = "invoice";
                $getResponse = $this->service->get($getRequest);
                if ($getResponse->readResponse->status->isSuccess) {
                    $getResponse->readResponse->record->creditMemo =
                        $this->_getCreditMemoData($getResponse->readResponse->record->internalId);
                    $invoiceData[] = $getResponse->readResponse->record;
                }
            }
        }
        return $invoiceData;
    }
}
