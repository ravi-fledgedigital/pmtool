<?php

namespace OnitsukaTiger\NetSuite\Model\SuiteTalk;

class SearchOrder extends \OnitsukaTiger\NetSuite\Model\SuiteTalk
{
    /**
     * Search order by external id
     * @param $id
     * @return \NetSuite\Classes\InventoryItem
     * @throws \Magento\Framework\Exception\InputException
     */
    public function searchByExternalId($id) {
        $service = $this->getService();

        $search = $this->getOrderSearch($id);

        $request = new \NetSuite\Classes\SearchRequest();
        $request->searchRecord = $search;

        /** @var \NetSuite\Classes\SearchResponse $searchResponse */
        $searchResponse = $service->search($request);
        /** @var \NetSuite\Classes\SearchResult $result */
        $result = $searchResponse->searchResult;

        if(!$result->status->isSuccess) {
            throw new \Magento\Framework\Exception\InputException(__('Failed API call : %1', $result->status->statusDetail));
        }
        if($result->totalRecords != 1) {
            throw new \Magento\Framework\Exception\InputException(__('result is not 1 record [%1]', $result->totalRecords));
        }
        /** @var \NetSuite\Classes\RecordList $list */
        $list = $result->recordList;
        /** @var \NetSuite\Classes\InventoryItem $item */
        $item = $list->record[0];

        return $item;
    }


    /**
     * Get order search
     * @param $id
     * @return \NetSuite\Classes\TransactionSearch
     */
    protected function getOrderSearch($id)
    {
        $searchStringField = new \NetSuite\Classes\SearchStringField();
        $searchStringField->operator = "is";
        $searchStringField->searchValue = $id;

        //call item search basic
        $transactionSearchBasic = new \NetSuite\Classes\TransactionSearchBasic();
        $transactionSearchBasic->externalIdString = $searchStringField;

        $transactionSearch = new \NetSuite\Classes\TransactionSearch();
        $transactionSearch->basic = $transactionSearchBasic;

        return $transactionSearch;
    }
}
