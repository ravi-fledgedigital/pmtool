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

namespace Magento\ProductOverrideDataExporter\Test\Integration;

use Magento\CatalogInventory\Model\Indexer\Stock;
use Magento\CatalogPermissions\Model\Indexer\Product as IndexerProductPermissions;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\Framework\App\ResourceConnection;
use Magento\Indexer\Model\Indexer;
use Magento\TestFramework\Helper\Bootstrap;
use RuntimeException;
use Throwable;

/**
 * Abstract class for Product Override tests
 */
class ProductOverrideTestAbstract extends \PHPUnit\Framework\TestCase
{
    /**
     * Product Override feed indexer
     */
    private const FEED_INDEXER = 'catalog_data_exporter_product_overrides';

    /**
     * Product Override feed table
     */
    private const FEED_INDEXER_TABLE = 'cde_product_overrides_feed';

    /**
     * @var FeedInterface
     */
    private $productOverrides;

    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var GroupManagementInterface
     */
    private $customerGroupManagement;

    /**
     * @var Stock
     */
    private $stockIndexer;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var IndexerProductPermissions
     */
    private $permissionsIndexer;

    public static function setUpBeforeClass(): void
    {
        Bootstrap::getObjectManager()->configure([
            'Magento\ProductOverrideDataExporter\Model\Indexer\ProductOverridesFeedIndexMetadata' => [
                'arguments' => [
                    'persistExportedFeed' => true
                ]
            ]
        ]);
    }

    /**
     * Setup tests
     */
    protected function setUp(): void
    {
        $this->productOverrides = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('productOverrides');
        $this->indexer = Bootstrap::getObjectManager()->create(Indexer::class);
        $this->customerGroupManagement = Bootstrap::getObjectManager()->create(GroupManagementInterface::class);
        $this->stockIndexer = Bootstrap::getObjectManager()->create(Stock::class);
        $this->permissionsIndexer = Bootstrap::getObjectManager()->create(IndexerProductPermissions::class);
        $this->resourceConnection = Bootstrap::getObjectManager()->create(ResourceConnection::class);
    }

    /**
     * Returns orderFeeds by IDs
     *
     * @param array $ids
     * @param array $customerGroupIds
     * @return array
     * @throws \Zend_Db_Statement_Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getProductOverrideFeedByIds(array $ids, ?array $customerGroupIds = null): array
    {
        if (empty($customerGroupIds)) {
            // TODO: currently made this functionality work with one customer group only (default)
            $customerGroupIds = [
                sha1((string)$this->customerGroupManagement->getDefaultGroup()->getId())
            ];
        }

        $output = [];
        foreach ($this->productOverrides->getFeedSince('1')['feed'] as $item) {
            if (!$item['deleted']
                && \in_array($item['productId'], $ids, false)
                && \in_array($item['customerGroupCode'], $customerGroupIds, false)) {
                $output[] = $item;
            }
        }
        return $output;
    }

    /**
     * Run the indexer to extract products override data
     * TODO: rewrite to reindex by ids
     *
     * @param bool $reindexPermissions
     * @return void
     *
     */
    protected function runIndexer(bool $reindexPermissions = false, bool $truncateOverridesFeed = false) : void
    {
        try {
            $this->stockIndexer->executeFull();
            if ($reindexPermissions) {
                $this->permissionsIndexer->executeFull();
            }
            if ($truncateOverridesFeed === true) {
                $this->truncateIndexTable();
            }
            $this->indexer->load(self::FEED_INDEXER);
            $this->indexer->reindexAll();
        } catch (Throwable) {
            throw new RuntimeException('Could not reindex products override data');
        }
    }

    /**
     * Truncates index table
     */
    private function truncateIndexTable(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $feedTable = $this->resourceConnection->getTableName(self::FEED_INDEXER_TABLE);
        $connection->truncateTable($feedTable);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->truncateIndexTable();
    }
}
