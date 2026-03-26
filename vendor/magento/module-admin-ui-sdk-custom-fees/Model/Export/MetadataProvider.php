<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
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

namespace Magento\AdminUiSdkCustomFees\Model\Export;

use Exception;
use Magento\AdminUiSdkCustomFees\Model\Cache\Cache;
use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Ui\Model\Export\MetadataProvider as BaseMetadataProvider;

/**
 * Metadata Provider for grid listing export.
 */
class MetadataProvider extends BaseMetadataProvider
{
    /**
     * @var array
     */
    private array $dataSource = [];

    /**
     * @var UiComponentInterface
     */
    private UiComponentInterface $currentComponent;

    /**
     * @var array
     */
    private array $registeredColumns = [];

    /**
     * @param Filter $filter
     * @param TimezoneInterface $localeDate
     * @param ResolverInterface $localeResolver
     * @param Cache $cache
     * @param string $dateFormat
     * @param array $data
     */
    public function __construct(
        Filter $filter,
        TimezoneInterface $localeDate,
        ResolverInterface $localeResolver,
        private Cache $cache,
        $dateFormat = 'M j, Y h:i:s A',
        array $data = []
    ) {
        parent::__construct($filter, $localeDate, $localeResolver, $dateFormat, $data);
    }

    /**
     * Returns columns list
     *
     * @param UiComponentInterface $component
     *
     * @return UiComponentInterface[]
     * @throws Exception
     */
    protected function getColumns(UiComponentInterface $component): array
    {
        $this->currentComponent = $component;
        $this->registeredColumns = $this->cache->getOrderCustomFees();
        return parent::getColumns($component);
    }

    /**
     * Returns row data
     *
     * @param DocumentInterface $document
     * @param array $fields
     * @param array $options
     *
     * @return string[]
     */
    public function getRowData(DocumentInterface $document, $fields, $options): array
    {
        if (!isset($this->currentComponent) || !($document instanceof Document)) {
            return parent::getRowData($document, $fields, $options);
        }

        $dataSourceItem = $this->prepareDataSource($this->currentComponent);
        $row = [];
        foreach ($fields as $column) {
            if (in_array($column, array_column($this->registeredColumns, 'id'))) {
                $row[] = $dataSourceItem[$column] ?? "";
            } else {
                $row[] = parent::getRowData($document, [$column], $options)[0];
            }
        }
        return $row;
    }

    /**
     * Prepare Data Source
     *
     * @param UiComponentInterface $component
     * @return array
     */
    private function prepareDataSource(UiComponentInterface $component): array
    {
        if (empty($this->dataSource)) {
            $context = $component->getContext();
            $dataSourceData = $context->getDataSourceData($component);
            $this->dataSource = $dataSourceData[$context->getDataProvider()->getName()]['config']['data']['items'];
        }
        return array_shift($this->dataSource);
    }
}
