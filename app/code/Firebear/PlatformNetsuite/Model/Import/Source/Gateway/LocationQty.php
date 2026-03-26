<?php

namespace Firebear\PlatformNetsuite\Model\Import\Source\Gateway;

use Magento\Checkout\Exception;
use NetSuite\Classes\ItemSearchAdvanced;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\SearchMoreWithIdRequest;
use NetSuite\Classes\SearchRequest;

/**
 * Class LocationQty
 * @package Firebear\PlatformNetsuite\Model\Import\Source\Gateway
 */
class LocationQty extends AbstractGateway
{

    /**
     * Upload Source
     *
     * @param $config
     * @return array
     */
    public function uploadPartSource($config = null)
    {
        $page = $config['page'];
        $savedSearchId = $config['saved_search_id'];
        $locations = [];
        $this->initService($config);
        $cachedSearchId = $this->getSearchId();

        if ($cachedSearchId && $page > 1) {
            $request = new SearchMoreWithIdRequest();
            $request->searchId = $cachedSearchId;
            $request->pageIndex = $page;
            $searchResponse = $this->service->searchMoreWithId($request);
            if ($searchResponse->searchResult->status->isSuccess) {
                $locations = $this->prepareResult($searchResponse->searchResult->searchRowList->searchRow);
            } else {
                $this->setSearchId(null);
            }
        } else {
            $this->service->setSearchPreferences(false, 20);
            $search = new ItemSearchAdvanced();
            $search->savedSearchId = $savedSearchId;
            $request = new SearchRequest();
            $request->searchRecord = $search;
            $searchResponse = $this->service->search($request);
            if ($searchResponse->searchResult->status->isSuccess) {
                $searchId = $searchResponse->searchResult->searchId;
                $this->setSearchId($searchId);
                $locations = $this->prepareResult($searchResponse->searchResult->searchRowList->searchRow);
            } else {
                $this->setSearchId(null);
            }
        }
        return $locations;
    }

    /**
     * @param $items
     * @return array
     */
    protected function prepareResult($items)
    {
        $result = [];
        foreach ($items as $item) {
            $basicRow = $item->basic;
            $inventoryLocation = $item->inventoryLocationJoin;
            $data = $this->convertResultToArray($basicRow);
            if (isset($inventoryLocation->name) && !empty($inventoryLocation->name)) {
                $inventoryLocationName = $inventoryLocation->name[0]->searchValue;
                $data['source_code'] = $this->getSourceCode($inventoryLocationName);
                $result[] = array_filter($data, function ($itemData) {
                    return !is_array($itemData);
                });
            }
        }
        return $result;
    }
}
