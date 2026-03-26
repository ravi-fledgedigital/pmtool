<?php
/**
 * Copyright © Firebear Studio, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Firebear\PlatformNetsuite\Model\Import\Source\Gateway;

use Firebear\ImportExport\Logger\Logger;
use Firebear\ImportExport\Traits\General as GeneralTrait;
use Magento\Framework\Serialize\SerializerInterface;
use NetSuite\Classes\GetRequest;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Netsuite product gateway
 */
class Product extends AbstractGateway
{
    use GeneralTrait;
    /**
     * Json Serializer
     *
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     *
     */
    protected $productRepository;

    /**
     * @var array
     */
    private $customListValues;

    /**
     * @var array
     */
    private $productData;

    /**
     * @var array
     */
    private $attributeMapping;

    /**
     * Product constructor.
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param SerializerInterface $serializer
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        SerializerInterface $serializer,
        Logger $logger,
        ConsoleOutput $output
    ) {
        parent::__construct($cache);
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->productRepository = $productRepository;
        $this->serializer = $serializer;
        $this->_logger = $logger;
        $this->output = $output;
    }

    /**
     * @param $offset
     * @param null $categoryId
     * @return bool
     */
    public function uploadPartSource($config = null)
    {
        $page = $config['page'];
        $savedSearchId = $config['saved_search_id'];
        $items = [];
        $this->initService($config);
        if (isset($config['netsuite_internal_id']) && $config['netsuite_internal_id']) {
            $items[] = $this->entityUpdateRequestHandler($config['netsuite_entity_type'], $config['netsuite_internal_id']);
            $items = $this->prepareResult($items, true);
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
                $this->setCustomLists(null);
                $this->setCustomFields(null);
                $this->service->setSearchPreferences(false, 20);
                $search = new \NetSuite\Classes\ItemSearchAdvanced();
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
            $items = $this->prepareGroupItems($items, $config);
        }
        $items = $this->prepareCustomFields($items, $config);
        return $items;
    }

    /**
     * @param $items
     * @return array
     */
    protected function prepareResult($items, $apiRequest = false)
    {
        $result = [];
        foreach ($items as $item) {
            if ($apiRequest) {
                $data = $item;
            } else {
                $basicRow = $item->basic;
                $data = $this->convertResultToArray($basicRow);
            }
            if (isset($data['internal_id']) && isset($data['internal_id']['internal_id'])) {
                $data['internal_id'] = $data['internal_id']['internal_id'];
                $productData = $this->getProductData($data['internal_id']);
                if (!empty($productData)) {
                    $data['entity_id'] = $productData['entity_id'];
                }
            }
            $data = $this->prepareTierPrices($data);
            $result[] = $data;
        }
        return $result;
    }

    /**
     * @param $data
     * @return mixed
     */
    private function prepareTierPrices($data)
    {
        if (isset($data['internal_id'])) {
            $priceLevels = [];
            $getItemRequest = new GetRequest();
            $itemBaseRef = new RecordRef();
            $itemBaseRef->internalId = $data['internal_id'];
            $itemBaseRef->type = RecordType::inventoryItem;
            $getItemRequest->baseRef = $itemBaseRef;
            $response = $this->service->get($getItemRequest);
            if ($response->readResponse->status->isSuccess) {
                $item = $response->readResponse->record;
                if (isset($item->pricingMatrix->pricing)) {
                    foreach ($item->pricingMatrix->pricing as $pricing) {
                        foreach ($pricing->priceList->price as $price) {
                            $priceLevels[$pricing->priceLevel->name][$price->quantity] = $price->value;
                        }
                    }
                }
            }
            if (!empty($priceLevels)) {
                $data['price_levels'] = json_encode($priceLevels);
            }
        }
        return $data;
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
                } elseif ($item instanceof \NetSuite\Classes\SearchColumnDoubleField && !empty($item->customLabel)) {
                    $result[$item->customLabel] = $this->convertResultToArray($item);
                } elseif ($item instanceof \NetSuite\Classes\ListOrRecordRef) {
                    $result[$item->internalId] = $this->convertResultToArray($item);
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
    private function prepareCustomFields($items, $config = null)
    {
        $customFields = $this->getCustomFields();
        $internalIds = [];
        foreach ($items as $key => $item) {
            foreach ($customFields as $internalId => $customFieldKey) {
                if (!isset($items[$key][$customFieldKey])) {
                    $items[$key][$customFieldKey] = null;
                }
                $internalIds[$customFieldKey] = $internalId;
            }

            if (isset($item['parent']) && isset($item['parent']['internal_id'])) {
                $items[$key]['parent'] = $item['parent']['internal_id'];
            }

            if (isset($item['custom_field_list']['custom_field'])) {
                $itemCustomFields = $item['custom_field_list']['custom_field'];
                foreach ($itemCustomFields as $customFieldKey => $customFieldValue) {
                    if (is_array($customFieldValue) && empty($customFieldValue['name'])) {
                        $customFieldName = $this->getCustomFieldName($customFieldValue);

                        if (!empty($config['import_custom_field_images'])) {
                            $customFieldRef = new \NetSuite\Classes\RecordRef();
                            $customFieldRef->type = RecordType::itemCustomField;
                            $customFieldRef->internalId = $internalIds[$customFieldKey];
                            $getRequest = new GetRequest();
                            $getRequest->baseRef = $customFieldRef;
                            $getResponse = $this->service->get($getRequest);
                            if ($getResponse->readResponse->status->isSuccess) {
                                $customFieldType = $getResponse->readResponse->record->fieldType;
                                if ($customFieldType == '_image' && !isset($items[$key]['base_image'])) {
                                    $items[$key]['base_image'] = $this->getCustomImage($customFieldValue['internal_id']);
                                } elseif ($customFieldType == '_image') {
                                    if (isset($items[$key]['additional_images'])) {
                                        $items[$key]['additional_images'] =
                                            $this->getCustomImage($customFieldValue['internal_id']) .
                                            $config['_import_multiple_value_separator'] .
                                            $items[$key]['additional_images'];
                                    } else {
                                        $items[$key]['additional_images'] =
                                            $this->getCustomImage($customFieldValue['internal_id']);
                                    }
                                }
                            }
                        }
                        if (!empty($customFieldName)) {
                            $items[$key][$customFieldKey] = $customFieldName;
                        }
                    } elseif (is_array($customFieldValue) && !empty($customFieldValue['name'])) {
                        $items[$key][$customFieldKey] = $customFieldValue['name'];
                    } elseif (is_bool($customFieldValue)) {
                        if ($customFieldValue) {
                            $items[$key][$customFieldKey] = 'Yes';
                        } else {
                            $items[$key][$customFieldKey] = 'No';
                        }
                    } else {
                        $items[$key][$customFieldKey] = $customFieldValue;
                    }
                }
                unset($item['custom_field_list']);
            }
        }
        return $items;
    }

    /**
     * Receiving url of image
     *
     * @param $internalId
     * @return string
     */
    private function getCustomImage($internalId)
    {
        $getRequest = new GetRequest();
        $file = new RecordRef();
        $file->internalId = $internalId;
        $file->type = RecordType::file;
        $getRequest->baseRef = $file;
        $response = $this->service->get($getRequest);
        if ($response->readResponse->status->isSuccess) {
            return $response->readResponse->record->url;
        } else {
            $errorMessage = __(
                'The image not imported' .
                ' Netsuite image Internal id: %1. Message: %2',
                [
                    $internalId,
                    $response->readResponse->status->statusDetail[0]->message
                ]
            );
            $this->addLogWriteln($errorMessage, $this->output, 'error');
            return '';
        }
    }

    /**
     * @param $customFieldValue
     * @return mixed|string
     */
    private function getCustomFieldName($customFieldValue)
    {
        $customFieldName = '';
        $customFieldValues = [];
        if (isset($customFieldValue['type_id'])) {
            $customFieldValues[] = $customFieldValue;
        } else {
            $customFieldValues = $customFieldValue;
        }
        foreach ($customFieldValues as $value) {
            if (is_array($value) && !isset($this->customListValues[$value['type_id']])) {
                $this->getCustomList($value['type_id']);
            }
            if (is_array($value) && !isset($this->customListValues[$value['type_id']])) {
                $typeId = $value['type_id'];
                $internalId = $value['internal_id'];
                if (isset($this->customListValues[$typeId][$internalId])) {
                    $customFieldName = empty($customFieldName) ?
                        $this->customListValues[$typeId][$internalId]
                        : $customFieldName . ',' . $this->customListValues[$typeId][$internalId];
                }
            }
        }

        return $customFieldName;
    }

    /**
     * @param $internalId
     */
    private function getCustomList($internalId)
    {
        $customList = $this->cache->load('netsuite_custom_list_values');
        if ($customList) {
            $customList = $this->serializer->unserialize($customList);
            if (isset($customList[$internalId])) {
                $this->customListValues = $customList;
            } else {
                $this->reloadCustomList($internalId);
            }
        } else {
            $this->reloadCustomList($internalId);
        }
    }

    /**
     * @param $internalId
     */
    private function reloadCustomList($internalId)
    {
        $operator = new \NetSuite\Classes\SearchMultiSelectFieldOperator();
        $searchBasic = new \NetSuite\Classes\CustomListSearchBasic();
        $multiSelectField = new \NetSuite\Classes\SearchMultiSelectField();
        $internalIdRecord = new \NetSuite\Classes\RecordRef();
        $internalIdRecord->internalId = $internalId;
        $multiSelectField->searchValue = [$internalIdRecord];
        $multiSelectField->operator = $operator::anyOf;
        $searchBasic->internalId = $multiSelectField;
        $search = new \NetSuite\Classes\CustomListSearch();
        $search->basic = $searchBasic;
        $request = new \NetSuite\Classes\SearchRequest();
        $request->searchRecord = $search;
        $searchResponse = $this->service->search($request);

        if ($searchResponse->searchResult->status->isSuccess
            && isset($searchResponse->searchResult->recordList->record[0])
        ) {
            if ($searchResponse->searchResult->recordList->record[0]
                && $searchResponse->searchResult->recordList->record[0]->customValueList) {
                $customValues = $searchResponse->searchResult->recordList->record[0]->customValueList;
                foreach ($customValues->customValue as $customValue) {
                    $this->customListValues[$internalId][$customValue->valueId] = $customValue->value;
                }
            } else {
                $this->customListValues[$internalId] = true;
            }
            $this->setCustomLists($this->customListValues);
        }
    }

    /**
     * @param $customLists
     */
    private function setCustomLists($customLists)
    {
        if ($customLists) {
            $customLists = $this->serializer->serialize($customLists);
        }
        $this->cache->save($customLists, 'netsuite_custom_list_values', [self::CACHE_TAG]);
    }

    /**
     * @return array|bool|float|int|string|null
     */
    private function getCustomFields()
    {
        $customFields = $this->cache->load('netsuite_item_custom_fields');
        if ($customFields) {
            $customFields = $this->serializer->unserialize($customFields);
            return $customFields;
        } else {
            $customFields = [];
            $getCustomizationIdRequest = new \NetSuite\Classes\GetCustomizationIdRequest();
            $customizationType = new \NetSuite\Classes\CustomizationType();
            $customizationType->getCustomizationType = \NetSuite\Classes\GetCustomizationType::itemCustomField;
            $getCustomizationIdRequest->customizationType = $customizationType;
            $getCustomizationIdRequest->includeInactives = false;
            $getCustomizationIdResponse = $this->service->getCustomizationId($getCustomizationIdRequest);
            if ($getCustomizationIdResponse->getCustomizationIdResult->status->isSuccess) {
                $customizationRef = $getCustomizationIdResponse->getCustomizationIdResult->customizationRefList
                    ->customizationRef;
                if (!empty($customizationRef)) {
                    foreach ($customizationRef as $customField) {
                        $customFields[$customField->internalId] = $customField->scriptId;
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
        if ($customFields) {
            $customFields = $this->serializer->serialize($customFields);
        }
        $this->cache->save($customFields, 'netsuite_item_custom_fields', [self::CACHE_TAG]);
    }

    /**
     * @param $items
     * @param $config
     * @return mixed
     */
    private function prepareGroupItems($items, $config)
    {
        $attributeMapping = $this->getAttributeMapping($config);
        $importBundleProduct = $config['import_item_group_to_bundle_product'];
        $simpleProductItems = [];
        foreach ($items as $key => $item) {
            if ($item['type'] == '_itemGroup') {
                $bundleValues = '';
                $associatedSkus = '';
                $getRequest = new \NetSuite\Classes\GetRequest();
                $getRequest->baseRef = new \NetSuite\Classes\RecordRef();
                $getRequest->baseRef->internalId = $item['internal_id'];
                $getRequest->baseRef->type = "itemGroup";
                $getResponse = $this->service->get($getRequest);

                if ($getResponse->readResponse->status->isSuccess) {
                    foreach ($getResponse->readResponse->record->memberList->itemMember as $itemMember) {
                        $simpleProductItem = $item;
                        $simpleProductItem['product_type'] = 'simple';
                        $simpleProductItem['display_name'] = $itemMember->item->name;
                        $simpleProductItem['name'] = substr(
                            strstr($itemMember->item->name, " "),
                            1
                        );
                        $simpleProductItem['internal_id'] = $itemMember->item->internalId;
                        $simpleProductItem['qty'] = $itemMember->quantity;

                        $simpleProductData = $this->getProductData($simpleProductItem['internal_id']);
                        if (!empty($simpleProductData)) {
                            $simpleProductItem['sku'] = $simpleProductData['sku'];
                        } else {
                            if (isset($attributeMapping['sku'])
                                && isset($simpleProductItem[$attributeMapping['sku']])
                            ) {
                                $simpleProductItem['sku'] = $simpleProductItem[$attributeMapping['sku']];
                            } else {
                                $simpleProductItem['sku'] = $simpleProductItem['internal_id'];
                            }
                        }
                        $simpleProductItems[] = $simpleProductItem;

                        if ($importBundleProduct) {
                            $bundleValues = $this->getBundleValues($simpleProductItem, $bundleValues);
                        } else {
                            $associatedSkus .=',%s=%s';
                            $associatedSkus = sprintf(
                                $associatedSkus,
                                $simpleProductItem['sku'],
                                $simpleProductItem['qty']
                            );
                        }
                    }
                    if ($importBundleProduct) {
                        $items[$key]['product_type'] = 'bundle';
                        $items[$key]['bundle_price_type'] = 'dynamic';
                        $items[$key]['bundle_sku_type'] = 'dynamic';
                        $items[$key]['bundle_price_view'] = 'Price range';
                        $items[$key]['bundle_weight_type'] = 'dynamic';
                        $items[$key]['bundle_shipment_type'] = 'together';
                        $items[$key]['bundle_values'] = trim($bundleValues, '|');
                    } else {
                        $items[$key]['product_type'] = 'grouped';
                        $items[$key]['associated_skus'] = $associatedSkus;
                    }
                }
            }
        }
        foreach ($simpleProductItems as $simpleProductItem) {
            array_unshift($simpleProductItem, $items);
        }
        return $items;
    }

    /**
     * @param $simpleProductItem
     * @param $bundleValues
     * @return string
     */
    private function getBundleValues($simpleProductItem, $bundleValues)
    {
        if (strpos($simpleProductItem['name'], '–')) {
            $delimiter = ' – ';
        } else {
            $delimiter = ' - ';
        }

        $productName = explode($delimiter, $simpleProductItem['name']);
        $bundleValues .= '|name=%s,type=select,required=0,sku=%s,price=0.0000,' .
            'default=0,default_qty=%s,price_type=fixed,can_change_qty=0';
        $bundleValues = sprintf(
            $bundleValues,
            array_shift($productName),
            $simpleProductItem['sku'],
            $simpleProductItem['qty']
        );

        return $bundleValues;
    }

    /**
     * @param $netsuiteInternalId
     * @return array
     */
    private function getProductData($netsuiteInternalId)
    {
        $data = [];
        if (!isset($this->productData[$netsuiteInternalId])) {
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
                    'entity_id' => $product->getId(),
                    'sku' => $product->getSku()
                ];
                $this->productData[$netsuiteInternalId] = $data;
                return $data;
            }
        } else {
            return $this->productData[$netsuiteInternalId];
        }
        return $data;
    }

    /**
     * @param $config
     * @return array
     */
    private function getAttributeMapping($config)
    {
        if (empty($this->attributeMapping)
            && isset($config['map'])
        ) {
            foreach ($config['map'] as $key => $map) {
                $this->attributeMapping[$map['system']] = $map['import'];
            }
        }
        return $this->attributeMapping;
    }
}
