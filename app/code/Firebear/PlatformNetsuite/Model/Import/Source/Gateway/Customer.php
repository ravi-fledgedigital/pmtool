<?php
/**
 * Copyright © Firebear Studio, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Firebear\PlatformNetsuite\Model\Import\Source\Gateway;

use Magento\Framework\Serialize\SerializerInterface;

/**
 * Netsuite customer gateway
 */
class Customer extends AbstractGateway
{
    /**
     * Json Serializer
     *
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * Customer constructor.
     * @param \Magento\Framework\App\CacheInterface $cache
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\App\CacheInterface $cache,
        SerializerInterface $serializer
    ) {
        parent::__construct($cache);
        $this->serializer = $serializer;
    }

    /**
     * @param null $config
     * @return array
     */
    public function uploadPartSource($config = null)
    {
        $page = $config['page'];
        $savedSearchId = $config['saved_search_id'];
        $items = [];
        $this->initService($config);
        if (isset($config['netsuite_internal_id']) && $config['netsuite_internal_id']) {
            $item = $this->entityUpdateRequestHandler($config['netsuite_entity_type'], $config['netsuite_internal_id']);
            if (isset($item['internal_id']) && isset($item['internal_id']['internal_id'])) {
                $item['internal_id'] = $item['internal_id']['internal_id'];
            }
            if (isset($item['price_level']) && isset($item['price_level']['internal_id'])) {
                $item['price_level'] = $item['price_level']['internal_id'];
            }
            foreach ($item as $key => $it) {
                if (is_array($it)) {
                    unset($item[$key]);
                }
            }
            $items[] = $item;
        } else {
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
                $this->setCustomFields(null);
                $this->service->setSearchPreferences(false, 20);
                $search = new \NetSuite\Classes\CustomerSearchAdvanced();
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
        }
        $items = $this->prepareCustomFields($items);
        return $items;
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
            if (isset($data['internal_id']) && isset($data['internal_id']['internal_id'])) {
                $data['internal_id'] = $data['internal_id']['internal_id'];
            }
            if (isset($data['price_level']) && isset($data['price_level']['internal_id'])) {
                $data['price_level'] = $data['price_level']['internal_id'];
            }
            $result[] = $data;
        }
        return $result;
    }

    /**
     * @param $data
     * @return array
     */
    protected function convertResultToArray($data)
    {
        $result = [];
        if (is_object($data)) {
            $objectProperties = $data::$paramtypesmap;
            foreach ($objectProperties as $objectProperty => $type) {
                if ($objectProperty == 'searchValue') {
                    $result = $this->convertResultToArray($data->$objectProperty);
                } else {
                    $objectPropertyUnderscore = preg_replace('/(?<=\\w)(?=[A-Z])/', "_$1", $objectProperty);
                    $objectPropertyUnderscore = strtolower($objectPropertyUnderscore);
                    $result[$objectPropertyUnderscore] = $this->convertResultToArray($data->$objectProperty);
                }
            }
        } elseif (is_array($data)) {
            $result = [];
            foreach ($data as $item) {
                if ($item instanceof \NetSuite\Classes\SearchColumnCustomField) {
                    $result[$item->scriptId] = $this->convertResultToArray($item);
                } else {
                    $result = $this->convertResultToArray($item);
                }
            }
        } else {
            $result = $data;
        }
        return $result;
    }

    /**
     * @param $items
     * @param $customFields
     * @return mixed
     */
    private function prepareCustomFields($items)
    {
        $customFields = $this->getCustomFields();
        $internalIds = [];
        foreach ($items as $key => $item) {
            if (in_array($item['internal_id'], $internalIds) || !$item['address_internal_id']) {
                unset($items[$key]);
                continue;
            } else {
                $internalIds[] = $item['internal_id'];
            }
            foreach ($customFields as $customFieldKey) {
                if (!isset($items[$key][$customFieldKey])) {
                    $items[$key][$customFieldKey] = null;
                }
            }
            if (isset($item['custom_field_list']['custom_field'])) {
                $itemCustomFields = $item['custom_field_list']['custom_field'];
                foreach ($itemCustomFields as $customFieldKey => $customFieldValue) {
                    $items[$key][$customFieldKey] = $customFieldValue;
                }
            }
        }
        $items = array_values($items);
        return $items;
    }

    /**
     * @return array|bool|float|int|string|null
     */
    private function getCustomFields()
    {
        $customFields = $this->cache->load('netsuite_entity_custom_fields');
        if ($customFields) {
            $customFields = $this->serializer->unserialize($customFields);
            return $customFields;
        } else {
            $customFields = [];
            $getCustomizationIdRequest = new \NetSuite\Classes\GetCustomizationIdRequest();
            $customizationType = new \NetSuite\Classes\CustomizationType();
            $customizationType->getCustomizationType = \NetSuite\Classes\GetCustomizationType::entityCustomField;
            $getCustomizationIdRequest->customizationType = $customizationType;
            $getCustomizationIdRequest->includeInactives = false;
            $getCustomizationIdResponse = $this->service->getCustomizationId($getCustomizationIdRequest);
            if ($getCustomizationIdResponse->getCustomizationIdResult->status->isSuccess) {
                $customizationRef = $getCustomizationIdResponse->getCustomizationIdResult->customizationRefList
                    ->customizationRef;
                if (!empty($customizationRef)) {
                    foreach ($customizationRef as $customField) {
                        $customFields[] = $customField->scriptId;
                    }
                }
            }
            $this->setCustomFields($customFields);
            return $customFields;
        }
    }

    /**
     * @param $customLists
     */
    private function setCustomFields($customFields)
    {
        if (is_array($customFields)) {
            $customFields = $this->serializer->serialize($customFields);
        }
        $this->cache->save($customFields, 'netsuite_entity_custom_fields', [self::CACHE_TAG]);
    }
}
