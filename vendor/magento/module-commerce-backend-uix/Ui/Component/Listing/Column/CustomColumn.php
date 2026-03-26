<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Ui\Component\Listing\Column;

use Magento\CommerceBackendUix\Model\Config;
use Magento\CommerceBackendUix\Model\Grid\ColumnsDataRetriever;
use Magento\CommerceBackendUix\Model\UiGridType;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Generic custom column to prepare data source for registered columns
 */
class CustomColumn extends Column
{
    private const API_MESH_BASE_URL = 'admin/admin_ui_sdk/api_mesh_base_url';
    private const API_MESH_BASE_URL_STAGE = 'admin/admin_ui_sdk/api_mesh_base_url_stage';
    private const MESH_ID = 'meshId';
    private const ATTRIBUTE_CODE = 'attribute_code';
    private const DATA_TYPE = 'data_type';
    private const DEFAULT_ID = '*';

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param ColumnsDataRetriever $dataRetriever
     * @param Config $uiSdkConfig
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private ScopeConfigInterface $scopeConfig,
        private ColumnsDataRetriever $dataRetriever,
        private Config $uiSdkConfig,
        protected $components = [],
        private array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $config = $this->getData('config');
            $namespace = $this->context->getNamespace();
            switch ($namespace) {
                case UiGridType::SALES_ORDER_GRID:
                    $this->fetchColumnsData($dataSource, $config, 'orders', 'orderGridColumns', 'increment_id');
                    break;
                case UiGridType::PRODUCT_LISTING_GRID:
                    $this->fetchColumnsData($dataSource, $config, 'products', 'productGridColumns', 'sku');
                    break;
                case UiGridType::CUSTOMER_GRID:
                    $this->fetchColumnsData($dataSource, $config, 'customers', 'customerGridColumns', 'entity_id');
                    break;
            }
        }
        return $dataSource;
    }

    /**
     * Fetch columns data using API Mesh
     *
     * @param array $dataSource
     * @param array $config
     * @param string $entity
     * @param string $gridColumn
     * @param string $idLabel
     * @return void
     */
    private function fetchColumnsData(
        array &$dataSource,
        array $config,
        string $entity,
        string $gridColumn,
        string $idLabel
    ): void {
        $filteredIds = array_column($dataSource['data']['items'], $idLabel);
        $externalData = $this->fetchExternalData($config, $entity, $gridColumn, $filteredIds);
        $columnName = $config[self::ATTRIBUTE_CODE];
        $defaultColumnValue = $externalData['data'][$entity][$gridColumn][self::DEFAULT_ID][$columnName] ?? '';

        foreach ($dataSource['data']['items'] as &$item) {
            $id = $item[$idLabel];
            $columnValue = $externalData['data'][$entity][$gridColumn][$id][$columnName] ?? '';
            $item[$this->getData('name')] = $columnValue ?: $defaultColumnValue;
        }
    }

    /**
     * Fetch external data for columns
     *
     * @param array $config
     * @param string $entity
     * @param string $gridColumn
     * @param array $filteredIds
     * @return array
     */
    private function fetchExternalData(array $config, string $entity, string $gridColumn, array $filteredIds): array
    {
        $url = $this->getApiMeshUrl($config);
        $query = sprintf(
            '{"query":"query { %s(ids:\"%s\") { %s } }"}',
            $entity,
            implode(",", $filteredIds),
            $gridColumn
        );

        return $this->dataRetriever->getColumnData(
            $url,
            $config[self::ATTRIBUTE_CODE],
            $config[self::DATA_TYPE],
            $query,
            $entity,
            $gridColumn
        );
    }

    /**
     * Get API Mesh URL based on configuration
     *
     * @param array $config
     * @return string
     */
    private function getApiMeshUrl(array $config): string
    {
        $meshId = $config[self::MESH_ID];

        $baseUrl = $this->scopeConfig->getValue(
            $this->uiSdkConfig->isTestingEnabled() ? self::API_MESH_BASE_URL_STAGE : self::API_MESH_BASE_URL
        );

        return sprintf('%s%s/graphql', $baseUrl, $meshId);
    }
}
