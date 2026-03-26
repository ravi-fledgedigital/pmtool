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

namespace Magento\ProductOverrideDataExporter\Model;

use Magento\CatalogPermissions\Model\Indexer\AbstractAction as CatalogPermissions;
use Magento\CatalogPermissions\Model\Indexer\Category\ModeSwitcher;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Mview\View\CollectionFactory as MviewCollectionFactory;
use Magento\Framework\Mview\View\CollectionInterface;
use Magento\Framework\Mview\View\StateInterface;
use Magento\Framework\Mview\View\Subscription;

/**
 * Class to maintain mView subscriptions for index dimension tables
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ViewTableMaintainer
{
    private ResourceConnection $resourceConnection;
    private CollectionFactory $collectionFactory;
    private CollectionInterface $viewCollection;
    private CacheInterface $cache;
    private ?ScopeConfigInterface $scopeConfig;
    private ?CommerceDataExportLoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CollectionFactory $collectionFactory
     * @param MviewCollectionFactory $viewCollectionFactory
     * @param CacheInterface $cache
     * @param ScopeConfigInterface|null $scopeConfig
     * @param CommerceDataExportLoggerInterface|null $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CollectionFactory $collectionFactory,
        MviewCollectionFactory $viewCollectionFactory,
        CacheInterface $cache,
        ?ScopeConfigInterface $scopeConfig = null,
        ?CommerceDataExportLoggerInterface $logger = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->collectionFactory = $collectionFactory;
        $this->viewCollection = $viewCollectionFactory->create();
        $this->cache = $cache;
        $this->scopeConfig = $scopeConfig
            ?? ObjectManager::getInstance()->get(ScopeConfigInterface::class);
        $this->logger = $logger
            ?? ObjectManager::getInstance()->get(CommerceDataExportLoggerInterface::class);
    }

    /**
     * Create subscriptions for index dimension tables
     *
     * @return void
     */
    public function createSubscriptions(): void
    {
        $viewTables  = $this->isDimensionModeEnabled()
            ? $this->getDimensionTables(false)
            : [$this->resourceConnection->getTableName($this->getMainIndexTable())];
        $viewList = $this->getViewsForTables($viewTables);
        foreach ($viewList as $view) {
            foreach ($view->getSubscriptions() as $subscriptionTemplate) {
                /* @var $subscription Subscription */
                $subscription = $view->initSubscriptionInstance($subscriptionTemplate);
                $subscription->create();
            }
        }
        $this->cache->clean(['collections']);
    }

    /**
     * Get view subscriptions for dimension tables
     *
     * @param array $subscriptions
     * @return array
     */
    public function getViewSubscriptionsForDimensionTables(array $subscriptions): array
    {
        $templateSubscription = $subscriptions[$this->getMainIndexTable()] ?? null;
        // Return provided subscriptions if no template for subscription found
        if (null === $templateSubscription) {
            return $subscriptions;
        }
        $dimensionalSubscriptions = [];

        foreach ($this->getDimensionTables(false) as $dimensionTableName) {
            $templateSubscription['name'] = $dimensionTableName;
            $dimensionalSubscriptions[$dimensionTableName] = $templateSubscription;
        }

        return $dimensionalSubscriptions;
    }

    /**
     * Remove subscriptions from dimension tables
     *
     * @return void
     */
    public function removeSubscriptions(): void
    {
        $tableNames = $this->getDimensionTables(false);
        $viewList = $this->getViewsForTables($tableNames);
        foreach ($viewList as $view) {
            $subscriptions = $view->getSubscriptions();
            foreach ($subscriptions as $subscriptionConfig) {
                /* @var $subscription Subscription */
                $subscription = $view->initSubscriptionInstance($subscriptionConfig);
                if (\in_array($subscription->getTableName(), $tableNames, true)) {
                    $subscription->remove();
                }
            }
        }
        $this->cache->clean(['collections']);
    }

    /**
     * Get dimension tables
     *
     * @param bool $prefix
     * @return array
     */
    public function getDimensionTables(bool $prefix = true): array
    {
        $tables = [];
        $tableName = $prefix
            ? $this->resourceConnection->getTableName($this->getMainIndexTable())
            : $this->getMainIndexTable();
        foreach ($this->collectionFactory->create()->getAllIds() as $customerGroupId) {
            $tables[] = $tableName . '_' . $customerGroupId;
        }
        return $tables;
    }

    /**
     * Get main table
     *
     * @return string
     */
    public function getMainIndexTable(): string
    {
        return CatalogPermissions::INDEX_TABLE . CatalogPermissions::PRODUCT_SUFFIX;
    }

    /**
     * Get list of views that are enabled for particular tables
     *
     * @param string[] $tableNames
     * @return array
     */
    private function getViewsForTables(array $tableNames): array
    {
        // Clear view collection to avoid loading legacy views
        $this->viewCollection->clear();
        // Get list of views that are enabled
        $allViewList = $this->viewCollection->getViewsByStateMode(StateInterface::MODE_ENABLED);
        $viewList = [];
        $dbPrefix = $this->resourceConnection->getTablePrefix();
        foreach ($tableNames as &$tableName) {
            $tableName = preg_replace("/^$dbPrefix/", '', $tableName);
        }

        foreach ($allViewList as $view) {
            $subscriptions = $view->getSubscriptions();
            if (array_intersect(array_keys($subscriptions), $tableNames)) {
                $viewList[] = $view;
            }
        }

        return $viewList;
    }

    /**
     * Check if dimension mode by customer groups is enabled
     *
     * @return bool
     */
    public function isDimensionModeEnabled(): bool
    {
        //AC <= 2.4.4 does not support dimensions for permissions, so adding ad-hoc fix to allow indexation for 2.4.4 and lower
        if (class_exists(ModeSwitcher::class) === false) {
            return false;
        }
        $isDimension = $this->scopeConfig->getValue(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE);
        return $isDimension === ModeSwitcher::DIMENSION_CUSTOMER_GROUP;
    }

    /**
     * Get dimension table name by customer group ID
     *
     * @param int $customerGroupId
     * @param bool $prefix
     * @return string
     */
    public function getDimensionTableByCustomerGroup(int $customerGroupId, bool $prefix = true): string
    {
        $tableName = $this->getMainIndexTable();
        if ($prefix) {
            $tableName = $this->resourceConnection->getTableName($tableName);
        }
        return $tableName . '_' . $customerGroupId;
    }

    /**
     * Create view table
     *
     * @deprecated custom view for dimensions is not being created anymore
     * @see \Magento\ProductOverrideDataExporter\Model\Query\DimensionalPermissionsIndexQuery::getDimensionModeCursor
     * @return void
     */
    public function createView(): void
    {
        $this->logger->warning('Creating view for dimension tables');
    }

    /**
     * Drop view table
     *
     * @deprecated custom view for dimensions is not being created anymore, so not to be removed as well.
     * @see \Magento\ProductOverrideDataExporter\Model\Query\DimensionalPermissionsIndexQuery::getDimensionModeCursor
     * @return void
     */
    public function dropView(): void
    {
        $this->logger->warning('Method ' . __METHOD__ . ' is deprecated and should not be used.'
            . ' Please check your implementation');
    }

    /**
     * Get view table
     *
     * @deprecated custom view for dimensions is not being created anymore. No custom view index created.
     * @see \Magento\ProductOverrideDataExporter\Model\Query\DimensionalPermissionsIndexQuery::getDimensionModeCursor
     * @return string
     */
    public function getViewIndexTable(): string
    {
        $this->logger->warning('Method ' . __METHOD__ . ' is deprecated and should not be used.'
            . ' Please check your implementation');
        return '';
    }
}
