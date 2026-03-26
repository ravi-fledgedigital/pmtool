<?php
/**
 * Copyright © Firebear Studio, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Firebear\PlatformNetsuite\Model\Import\Source\Gateway;

/**
 * Netsuite category gateway
 */
class Category extends AbstractGateway
{
    /**
     * @param $offset
     * @param null $categoryId
     * @return array
     */
    public function uploadPartSource($config = null)
    {
        $page = $config['page'];
        $savedSearchId = $config['saved_search_id'];
        $items = [];
        $this->initService($config);
        $cachedSearchId = $this->getSearchId();

        if ($cachedSearchId && $page > 1) {
            $request = new \NetSuite\Classes\SearchMoreWithIdRequest();
            $request->searchId = $cachedSearchId;
            $request->pageIndex = $page;
            $searchResponse = $this->service->searchMoreWithId($request);
            if ($searchResponse->searchResult->status->isSuccess) {
                $items = $this->prepareResult($searchResponse->searchResult->searchRowList->searchRow);
            } else {
                $this->setSearchId(null);
            }
        } else {
            $this->service->setSearchPreferences(false, 100);
            $search = new \NetSuite\Classes\SiteCategorySearchAdvanced();
            $search->savedSearchId = $savedSearchId;
            $request = new \NetSuite\Classes\SearchRequest();
            $request->searchRecord = $search;
            $searchResponse = $this->service->search($request);
            if ($searchResponse->searchResult->status->isSuccess) {
                $searchId = $searchResponse->searchResult->searchId;
                $this->setSearchId($searchId);
                $items = $this->prepareResult($searchResponse->searchResult->searchRowList->searchRow);
            } else {
                $this->setSearchId(null);
            }
        }
        return $items;
    }
}
