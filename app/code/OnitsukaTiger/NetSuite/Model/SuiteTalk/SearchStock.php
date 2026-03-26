<?php

namespace OnitsukaTiger\NetSuite\Model\SuiteTalk;

class SearchStock extends \OnitsukaTiger\NetSuite\Model\SuiteTalk
{
    const CONFIG_PATH_SAVED_SEARCH_ID = 'netsuite/saved_search_id/inventory_search';

    /**
     * Search product by sku
     * @param $sku
     * @param $websiteId
     * @return \NetSuite\Classes\InventoryItem
     * @throws \Magento\Framework\Exception\InputException
     */
    public function searchBySku($sku, $websiteId) {
        $service = $this->getService();

        $savedSearchId = $this->scopeConfig->getValue(
            self::CONFIG_PATH_SAVED_SEARCH_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );

        $itemSearch = $this->getSkuItemSearch($sku);

        // TODO should set saved search ID for each country (1708 is for stage - thailand)
        $itemSearchAdvanced = new \NetSuite\Classes\ItemSearchAdvanced();
        $itemSearchAdvanced->savedSearchId = $savedSearchId;
        $itemSearchAdvanced->criteria = $itemSearch;

        $request = new \NetSuite\Classes\SearchRequest();
        $request->searchRecord = $itemSearchAdvanced;

        /** @var \NetSuite\Classes\SearchResponse $searchResponse */
        $searchResponse = $service->search($request);
        /** @var \NetSuite\Classes\SearchResult $result */
        $result = $searchResponse->searchResult;

        if(!$result->status->isSuccess) {
            $msg = sprintf('Failed API call : %s', $result->status->statusDetail);
            throw new \Magento\Framework\Exception\InputException($msg);
        }
        if($result->totalRecords != 1) {
            throw new \Magento\Framework\Exception\InputException('result is not 1 record');
        }
        /** @var \NetSuite\Classes\SearchRowList $list */
        $list = $result->searchRowList;
        /** @var \NetSuite\Classes\ItemSearchRow $item */
        $item = $list->searchRow[0];
        /** @var \NetSuite\Classes\SearchColumnDoubleField $quantity */
        $quantity = $item->basic->locationQuantityAvailable[0];

        return $quantity->searchValue;
    }
}
