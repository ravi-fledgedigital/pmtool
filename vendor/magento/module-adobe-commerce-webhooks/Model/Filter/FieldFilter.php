<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Model\Filter;

use Magento\AdobeCommerceWebhooks\Model\Filter\Converter\HookFieldConverter;

/**
 * Retrieves values from an input data payload.
 */
class FieldFilter
{
    /**
     * @param HookFieldConverter $hookFieldConverter
     */
    public function __construct(
        private HookFieldConverter $hookFieldConverter
    ) {
    }

    /**
     * Uses an input Field element to perform data filtration.
     *
     * @param Field $field
     * @param array $processingData
     * @param array $unfilteredData
     * @return array|mixed|null
     */
    public function processField(Field $field, array $processingData, array $unfilteredData)
    {
        $filteredData = [];

        $children = $field->getChildren();

        if (empty($children)) {
            return $this->processTailField($field, $processingData, $unfilteredData);
        }

        $arrayValueExists = isset($processingData[$field->getName()]) && is_array($processingData[$field->getName()]);
        if ($field->isArray()) {
            if (!$arrayValueExists) {
                return [];
            }

            foreach ($processingData[$field->getName()] as $dataItem) {
                $filteredData[] = $this->processChildren(
                    $children,
                    is_array($dataItem) ? $dataItem : [],
                    $unfilteredData
                );
            }
        } else {
            $filteredData = array_replace_recursive(
                $filteredData,
                $this->processChildren(
                    $children,
                    $arrayValueExists ? $processingData[$field->getName()] : [],
                    $unfilteredData
                )
            );
        }

        return $filteredData;
    }

    /**
     * Processes children Field elements.
     *
     * @param Field[] $children
     * @param array $processingData
     * @param array $unfilteredData
     * @return array
     */
    private function processChildren(array $children, array $processingData, array $unfilteredData): array
    {
        $result = [];

        foreach ($children as $child) {
            if ($child->hasChildren()) {
                $result[$child->getName()] = $this->processField($child, $processingData, $unfilteredData);
            } else {
                $result[$child->getName()] = $this->processTailField($child, $processingData, $unfilteredData);
            }
        }
        return $result;
    }

    /**
     * Returns the extracted and converted, if applicable, value for Field which has no children.
     *
     * @param Field $field
     * @param array $processingData
     * @param array $unfilteredData
     * @return array|mixed|null
     */
    private function processTailField(Field $field, array $processingData, array $unfilteredData)
    {
        $fieldValue = $this->getTailFieldValue($field, $processingData);
        if ($fieldValue != null && $field->getConverterClass() != null) {
            $fieldValue = $this->hookFieldConverter->convertToExternalFormat(
                $fieldValue,
                $field->getHookField(),
                $unfilteredData
            );
        }
        return $fieldValue;
    }

    /**
     * Extracts the value for a Field which has no children.
     *
     * Removes index from array type fields.
     *
     * @param Field $field
     * @param array $data
     * @return array|mixed|null
     */
    private function getTailFieldValue(Field $field, array $data)
    {
        if (!isset($data[$field->getName()])) {
            return null;
        }

        if ($field->isArray() && is_array($data[$field->getName()])) {
            return array_values($data[$field->getName()]);
        }

        return $data[$field->getName()];
    }
}
