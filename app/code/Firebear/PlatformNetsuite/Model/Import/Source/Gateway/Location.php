<?php

namespace Firebear\PlatformNetsuite\Model\Import\Source\Gateway;

use Magento\Checkout\Exception;
use NetSuite\Classes\LocationSearchAdvanced;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\SearchMoreWithIdRequest;
use NetSuite\Classes\SearchRequest;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

/**
 * Class Location
 * @package Firebear\PlatformNetsuite\Model\Import\Source\Gateway
 */
class Location extends AbstractGateway
{
    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * Location constructor.
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param SourceRepositoryInterface $sourceRepository
     */
    public function __construct(
        \Magento\Framework\App\CacheInterface $cache,
        SourceRepositoryInterface $sourceRepository
    )
    {
        $this->sourceRepository = $sourceRepository;
        parent::__construct($cache);
    }

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
            $search = new LocationSearchAdvanced();
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
            $data = $this->convertResultToArray($basicRow);
            $data['source_code'] = $this->getSourceCode($data['name']);
            if (isset($this->countryMapping[$data['country']])) {
                $data['country'] = $this->countryMapping[$data['country']];
            }
            if (isset($data['internalId']) && isset($data['internalId']['internalId'])) {
                $data['netsuite_internal_id'] = $data['internalId']['internalId'];
            }
            if ($data['latitude'] == null) {
                $data['latitude'] = 'Not set';
            }
            if ($data['longitude'] == null) {
                $data['longitude'] = 'Not set';
            }
            $result[] = array_filter($data, function ($itemData) {
                return !is_array($itemData);
            });
        }
        return $result;
    }
}
