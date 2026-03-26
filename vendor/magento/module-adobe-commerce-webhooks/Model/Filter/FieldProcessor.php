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

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextPool;
use Magento\AdobeCommerceWebhooks\Model\Filter\Converter\HookFieldConverter;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookField;

/**
 * Produces a filtered data payload given a list of HookFields
 */
class FieldProcessor implements FieldProcessorInterface
{
    /**
     * @param ContextPool $contextPool
     * @param ContextRetriever $contextRetriever
     * @param FieldConverter $fieldConverter
     * @param FieldFilter $fieldFilter
     * @param HookFieldConverter $hookFieldConverter
     */
    public function __construct(
        private ContextPool $contextPool,
        private ContextRetriever $contextRetriever,
        private FieldConverter $fieldConverter,
        private FieldFilter $fieldFilter,
        private HookFieldConverter $hookFieldConverter
    ) {
    }

    /**
     * Filters the input data payload given a list of HookFields and adds any context values specified by a HookField.
     *
     * Returns full payload without filtering if hook doesn't have configured fields.
     *
     * @param array $data
     * @param HookField[] $hookFields
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function process(array $data, array $hookFields): array
    {
        if (empty($hookFields)) {
            return $data;
        }

        $filteredData = [];
        $fieldsByPath = [];
        foreach ($hookFields as $hookField) {
            if ($hookField->shouldRemove()) {
                continue;
            }

            $source = $hookField->getSource() ?: $hookField->getName();
            $key = empty($hookField->getSource()) ? '' : $hookField->getName();

            $sourceParts = explode('.', $source);
            if ($this->contextPool->has($sourceParts[0])) {
                $sourceValue = $this->contextRetriever->getContextValue($source, $hookField->getHook());
                if ($hookField->getConverter() !== null) {
                    $sourceValue = $this->hookFieldConverter->convertToExternalFormat($sourceValue, $hookField, $data);
                }
                $filteredData = array_merge_recursive(
                    $filteredData,
                    $this->formatDataPair(explode('.', $hookField->getName()), $sourceValue)
                );
                continue;
            }

            if (str_contains($key, '[]')) {
                $key = explode('[]', $key, 2)[0] . '[]';
            }
            if (isset($fieldsByPath[$key])) {
                array_push($fieldsByPath[$key], $hookField);
            } else {
                $fieldsByPath[$key] = [$hookField];
            }
        }

        return $this->buildFilteredData($fieldsByPath, $data, $filteredData);
    }

    /**
     * Uses input non-context HookFields, grouped by source, and input webhook data to build a filtered data array.
     *
     * @param array $hookFieldsBySource
     * @param array $inputData
     * @param array $filteredData
     * @return array
     */
    private function buildFilteredData(array $hookFieldsBySource, array $inputData, array $filteredData): array
    {
        foreach ($hookFieldsBySource as $key => $hookFields) {
            $fields = $this->fieldConverter->convert($hookFields);
            foreach ($fields as $field) {
                $processedData = [
                    $field->getName() => $this->fieldFilter->processField($field, $inputData, $inputData)
                ];

                if (!empty($key)) {
                    $processedData = $this->extractData($hookFields[0]->getSource(), $processedData);
                    $processedData = $this->formatDataPair(explode('.', $key), $processedData);
                }
                $filteredData = array_merge_recursive($filteredData, $processedData);
            }
        }

        return $filteredData;
    }

    /**
     * Extracts the data located at the path provided by the input source in the input array.
     *
     * @param string $source
     * @param array $data
     * @return array|mixed|null
     */
    private function extractData(string $source, array $data)
    {
        $sourceParts = explode('.', $source);

        foreach ($sourceParts as $sourcePart) {
            if (str_contains($sourcePart, '[]')) {
                $sourcePart = str_replace('[]', '', $sourcePart);
                return $data[$sourcePart] ?? [];
            }

            if (!isset($data[$sourcePart])) {
                return null;
            }

            $data = $data[$sourcePart];
        }

        return $data;
    }

    /**
     * Creates an array where the path to the input value is represented by the input array.
     *
     * @param array $nameParts
     * @param mixed $value
     * @return array|mixed
     */
    private function formatDataPair(array $nameParts, mixed $value)
    {
        if (empty($nameParts)) {
            return $value;
        }

        if (str_contains($nameParts[0], '[]')) {
            $key = str_replace('[]', '', $nameParts[0]);
            return [$key => $this->formatDataPair(array_slice($nameParts, 1), $value)];
        }

        return [$nameParts[0] => $this->formatDataPair(array_slice($nameParts, 1), $value)];
    }
}
