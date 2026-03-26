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

namespace Magento\ProductOverrideDataExporter\Model\Provider;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\DataExporter\Export\DataProcessorInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
use Magento\ProductOverrideDataExporter\Model\Query\DimensionalPermissionsIndexQuery;
use Magento\ProductOverrideDataExporter\Model\ViewTableMaintainer;
use Magento\QueryXml\Model\QueryProcessor;
use Magento\Store\Model\ScopeInterface;

/**
 * Provider for product overrides
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductOverrides implements DataProcessorInterface
{
    /**
     * Batch size for dimensional index tables
     */
    private const DIMENSIONAL_BATCH_SIZE = 50;
    /**
     * @var LoggerInterface
     */
    private $logger;

    private QueryProcessor $queryProcessor;

    private ScopeConfigInterface $scopeConfig;

    private ViewTableMaintainer $viewTableMaintainer;

    private DimensionalPermissionsIndexQuery $query;

    /**
     * @param LoggerInterface $logger
     * @param QueryProcessor $queryProcessor
     * @param ScopeConfigInterface|null $scopeConfig
     * @param ViewTableMaintainer|null $viewTableMaintainer
     * @param DimensionalPermissionsIndexQuery|null $query
     */
    public function __construct(
        LoggerInterface $logger,
        QueryProcessor $queryProcessor,
        ?ScopeConfigInterface $scopeConfig = null,
        ?ViewTableMaintainer $viewTableMaintainer = null,
        ?DimensionalPermissionsIndexQuery $query = null
    ) {
        $this->logger = $logger;
        $this->queryProcessor = $queryProcessor;
        $this->scopeConfig = $scopeConfig
            ?? ObjectManager::getInstance()->get(ScopeConfigInterface::class);
        $this->viewTableMaintainer = $viewTableMaintainer
            ?? ObjectManager::getInstance()->get(ViewTableMaintainer::class);
        $this->query = $query
            ?? ObjectManager::getInstance()->get(DimensionalPermissionsIndexQuery::class);
    }

    /**
     * Get provider data
     *
     * @param array $arguments
     * @param callable $dataProcessorCallback
     * @param FeedIndexMetadata $metadata
     * @param ? $node
     * @param ? $info
     * @return void
     * @throws UnableRetrieveData
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(
        array $arguments,
        callable $dataProcessorCallback,
        FeedIndexMetadata $metadata,
        $node = null,
        $info = null
    ): void {
        try {
            $categoryPermissionsEnabled = $this->scopeConfig->getValue(
                ConfigInterface::XML_PATH_ENABLED,
                ScopeInterface::SCOPE_STORE
            );
            if ($categoryPermissionsEnabled) {
                $output = [];
                $batchSize = $metadata->getBatchSize();
                foreach ($arguments as $value) {
                    $queryArguments['entityIds'][] = $value['productId'];
                }
                $itemN = 0;
                $queryArguments['entityIds'] = isset($queryArguments['entityIds'])
                    ? \array_unique($queryArguments['entityIds'])
                    : [];
                if ($this->viewTableMaintainer->isDimensionModeEnabled()) {
                    $dimensionIndexTables = $this->viewTableMaintainer->getDimensionTables();
                    foreach (array_chunk($dimensionIndexTables, self::DIMENSIONAL_BATCH_SIZE) as $chunk) {
                        $cursor = $this->query->getDimensionModeCursor(
                            $queryArguments['entityIds'],
                            $chunk
                        );
                        $this->processByBatch(
                            $cursor,
                            $dataProcessorCallback,
                            $output,
                            $batchSize,
                            $itemN
                        );
                    }
                } else {
                    $cursor = $this->queryProcessor->execute('productCategoryPermissions', $queryArguments);
                    $this->processByBatch(
                        $cursor,
                        $dataProcessorCallback,
                        $output,
                        $batchSize,
                        $itemN
                    );
                }
                if ($output) {
                    $dataProcessorCallback($this->get($output));
                }
            }

        } catch (\Exception $exception) {
            $this->logger->critical($exception, ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve product override data');
        }
    }

    /**
     * For backward compatibility - to allow 3rd party plugins work
     *
     * @param array $values
     * @return array
     */
    public function get(array $values) : array
    {
        return $values;
    }

    /**
     * Prepare output for row
     *
     * @param array $row
     * @return array
     */
    private function prepareOutput(array $row): array
    {
        $output['sku'] = $row['sku'];
        $output['productId'] = $row['productId'];
        $output['websiteCode'] = $row['websiteCode'];
        $output['customerGroupCode'] = $row['customerGroupCode'];
        $output['displayable'] = isset($row['displayable']) && $row['displayable'] == -1;
        $output['priceDisplayable'] = isset($row['priceDisplayable'])
            && $row['priceDisplayable'] == -1;
        $output['addToCartAllowed'] = isset($row['addToCartAllowed'])
            && $row['addToCartAllowed'] == -1;

        return $output;
    }

    /**
     * Process data by batches
     *
     * @param \Zend_Db_Statement_Interface $cursor
     * @param callable $dataProcessorCallback
     * @param array $output
     * @param int $batchSize
     * @param int $itemN
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    private function processByBatch(
        \Zend_Db_Statement_Interface $cursor,
        callable $dataProcessorCallback,
        array &$output,
        int $batchSize,
        int &$itemN
    ): void {
        while ($row = $cursor->fetch()) {
            $itemN++;
            $key = $row['productId'] . $row['websiteCode'] . $row['customerGroupCode'];
            $output[$key] = $this->prepareOutput($row);
            if ($itemN % $batchSize === 0) {
                $dataProcessorCallback($this->get($output));
                $output = [];
            }
        }
    }
}
