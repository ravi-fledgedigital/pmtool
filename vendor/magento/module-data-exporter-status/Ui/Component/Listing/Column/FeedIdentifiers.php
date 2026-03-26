<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2025 Adobe
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
 */
declare(strict_types=1);

namespace Magento\DataExporterStatus\Ui\Component\Listing\Column;

use Magento\DataExporter\Model\FeedMetadataPool;
use Magento\DataExporterStatus\Service\FeedIdentifiersProvider;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Actions
 */
class FeedIdentifiers extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param FeedIdentifiersProvider $feedIdentifiersProvider
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface                         $context,
        UiComponentFactory                       $uiComponentFactory,
        private readonly FeedIdentifiersProvider $feedIdentifiersProvider,
        array                                    $components = [],
        array                                    $data = [],
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        $dataSource = parent::prepareDataSource($dataSource);

        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                $identifiers = [];
                $feedData = json_decode((string) $item['feed_data'], true);
                if (\is_array($feedData)) {
                    foreach ($this->feedIdentifiersProvider->getIdentifiers($item['feed']) as $field) {
                        $value = $this->getValue($feedData, $field);
                        if ($value !== null) {
                            $identifiers[$field] = $value;
                            $item[$fieldName] = $value;
                        }
                    }
                    $item[$fieldName] = $this->formatFeedIdentifiers($identifiers);
                }
            }
        }

        return $dataSource;
    }

    /**
     * Check if identifier field is nested (contains dot notation)
     *
     * @param string $identifierField
     * @return bool
     */
    private function isNested(string $identifierField): bool
    {
        return str_contains($identifierField, '.');
    }

    /**
     * Get value from feed data, supporting nested fields using dot notation
     *
     * @param array $feedData
     * @param string $field
     * @return mixed
     */
    private function getValue(array $feedData, string $field): mixed
    {
        if (isset($feedData[$field])) {
            return $feedData[$field];
        } elseif ($this->isNested($field)) {
            $arrayPath = explode('.', $field);
            $reduce = (fn(array $source, $key) => (array_key_exists($key, $source)) ? $source[$key] : null);
            return array_reduce($arrayPath, $reduce, $feedData);
        }
        return null;
    }

    /**
     * Format feed identifiers array as a prettified string
     *
     * @param array $feedIdentifiers
     * @return string
     */
    private function formatFeedIdentifiers(array $feedIdentifiers): string
    {
        if (empty($feedIdentifiers)) {
            return __('No identifiers (new item)')->render();
        }

        foreach ($feedIdentifiers as $key => $value) {
                $feedIdentifiers[$key] = '<strong>' . $key . '</strong>: ' . $value;
        }

        return implode(', ', $feedIdentifiers);
    }
}
