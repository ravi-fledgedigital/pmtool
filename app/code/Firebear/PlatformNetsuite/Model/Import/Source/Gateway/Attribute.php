<?php

namespace Firebear\PlatformNetsuite\Model\Import\Source\Gateway;

use Magento\Checkout\Exception;
use NetSuite\Classes\RecordType;

class Attribute
{
    /**
     * @var array
     */
    protected $mappingFieldType = [
        '_multipleSelect' => 'multiselect',
        '_freeFormText' => 'text',
        '_listRecord' => 'select',
        '_textArea' => 'textarea',
        '_inlineHtml' => 'texteditor',
        '_date' => 'date',
        '_image' => 'media_image'
    ];

    /**
     * Upload Source
     *
     * @param $config
     * @return array
     */
    public function uploadSource($config = null)
    {
        $items = [];
        $config = [
            "endpoint" => $config['endpoint'],
            "host" => $config['host'],
            "account" => $config['account'],
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

        $service = new \NetSuite\NetSuiteService($config, $options);
        $getRequest = new \NetSuite\Classes\GetRequest();
        $getRequest->baseRef = new \NetSuite\Classes\RecordRef();
        $allCustomItemFields = $this->getAllItemCustomFields($service);
        foreach ($allCustomItemFields as $customItemField) {
            $item = [];
            $item['scriptId'] = $customItemField->scriptId;
            $item['internalId'] = $customItemField->internalId;
            $item['externalId'] = $customItemField->externalId;
            $item['type'] = $customItemField->type;
            $item['name'] = $customItemField->name;

            //Get detail data for itemCustomField
            $getRequest->baseRef->internalId = $customItemField->internalId;
            $getRequest->baseRef->type = "itemCustomField";
            $detailCustomItemField = (array)$service->get($getRequest)->readResponse->record;
            $notEmptyAppliesToInventory = (
                isset($detailCustomItemField['appliesToInventory']) && $detailCustomItemField['appliesToInventory']
            );
            $notEmptyAppliesToNoInventory = (
                isset($detailCustomItemField['appliesToNoInventory']) && $detailCustomItemField['appliesToNoInventory']
            );
            if ($notEmptyAppliesToInventory || $notEmptyAppliesToNoInventory) {
                $item = array_merge($item, $detailCustomItemField);
                if ($item['selectRecordType']) {
                    $getRequest->baseRef->internalId
                        = $item['selectRecordType']->internalId;
                    $getRequest->baseRef->type = RecordType::customList;
                    $getResponse = $service->get($getRequest);
                    $record = $getResponse->readResponse->record;
                    if ($record) {
                        $customValueList = $record->customValueList;
                        $options = $customValueList->customValue;
                        $item['options'] = $options;
                    }
                }
                $item = $this->prepareResult($item);
                $items = array_merge($items, $item);
            }
        }
        return $items;
    }

    /**
     * Get all item custom fields
     *
     * @param $service
     * @return array
     */
    private function getAllItemCustomFields($service)
    {
        $getCustomizationIdRequest = new \NetSuite\Classes\GetCustomizationIdRequest();
        $customizationType = new \NetSuite\Classes\CustomizationType();
        $customizationType->getCustomizationType = \NetSuite\Classes\GetCustomizationType::itemCustomField;
        $getCustomizationIdRequest->customizationType = $customizationType;
        $getCustomizationIdRequest->includeInactives = false;
        try {
            $itemCustomFields = $service
                ->getCustomizationId($getCustomizationIdRequest)
                ->getCustomizationIdResult
                ->customizationRefList
                ->customizationRef;
        } catch (Exception $e) {
            return [];
        }
        return $itemCustomFields;
    }

    /**
     * Prepare result
     *
     * @param $item
     * @return array
     */
    private function prepareResult($item)
    {
        $result = [];
        if (isset($item['options'])) {
            foreach ($item['options'] as $options) {
                $item['frontend_input'] = $this->mappingFieldType[$item['fieldType']];
                $item['option:value'] = $options->value;
                $item['option:sort_order'] = $options->valueId;
                $result[] = array_filter($item, function ($itemData) {
                    return !is_array($itemData) && !is_object($itemData);
                });
            }
        } else {
            if (in_array($item['fieldType'], $this->mappingFieldType)) {
                $item['frontend_input'] = $this->mappingFieldType[$item['fieldType']];
                $result[] = array_filter($item, function ($itemData) {
                    return !is_array($itemData) && !is_object($itemData);
                });
            }
        }
        return $result;
    }
}
