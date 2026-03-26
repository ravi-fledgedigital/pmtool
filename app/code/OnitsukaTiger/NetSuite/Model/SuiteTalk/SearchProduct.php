<?php

namespace OnitsukaTiger\NetSuite\Model\SuiteTalk;

class SearchProduct extends \OnitsukaTiger\NetSuite\Model\SuiteTalk
{
    /**
     * Search product by sku
     * @param $sku
     * @return \NetSuite\Classes\InventoryItem
     * @throws \Magento\Framework\Exception\InputException
     */
    public function searchBySku($sku) {
        $service = $this->getService();

        $itemSearch = $this->getSkuItemSearch($sku);

        $request = new \NetSuite\Classes\SearchRequest();
        $request->searchRecord = $itemSearch;

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
     * Search enable / disable flag by SKU
     * @param $sku
     * @throws \Magento\Framework\Exception\InputException
     */
    public function searchEnableFlagBySku($sku)
    {
        /** @var \NetSuite\Classes\InventoryItem $item */
        $item = $this->searchBySku($sku);
        /** @var \NetSuite\Classes\CustomFieldRef $customField */
        $value = [];
        foreach($item->customFieldList->customField as $customField) {
            if($customField->scriptId == \OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct::SCRIPT_ID_CUSTITEM_ECOMM_PRODUCT) {
                $value = $customField->value;
                break;
            }
        }
        return $value;
    }
}
