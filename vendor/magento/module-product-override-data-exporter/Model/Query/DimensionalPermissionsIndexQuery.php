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

namespace Magento\ProductOverrideDataExporter\Model\Query;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\ColumnValueExpressionFactory;

/**
 * Prepare queries to multiple permission index tables in case when dimension mode is enabled
 */
class DimensionalPermissionsIndexQuery
{
    private ResourceConnection $resourceConnection;
    private FeedIndexMetadata $feedIndexMetadata;
    private ColumnValueExpressionFactory $expressionFactory;

    /**
     * @param ResourceConnection $resourceConnection
     * @param FeedIndexMetadata $feedIndexMetadata
     * @param ColumnValueExpressionFactory $expressionFactory
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        FeedIndexMetadata $feedIndexMetadata,
        ColumnValueExpressionFactory $expressionFactory
    ) {
        $this->feedIndexMetadata = $feedIndexMetadata;
        $this->resourceConnection = $resourceConnection;
        $this->expressionFactory = $expressionFactory;
    }

    /**
     * Get DB statement to multiple permission index tables in case when dimension mode is enabled
     *
     * @param array $productIds
     * @param array $listOfTables
     * @return \Zend_Db_Statement_Interface
     */
    public function getDimensionModeCursor(array $productIds, array $listOfTables): \Zend_Db_Statement_Interface
    {
        $sourceTableName = $this->feedIndexMetadata->getSourceTableName();
        $connection = $this->resourceConnection->getConnection();
        $sql = $connection->select()
            ->from(
                ['cpe' => $this->resourceConnection->getTableName($sourceTableName)],
                [
                    'sku' => 'cpe.sku'
                ]
            )
            ->joinInner(
                ['s' => $this->resourceConnection->getTableName('store')],
                '',
                []
            )
            ->joinInner(
                ['sw' => $this->resourceConnection->getTableName('store_website')],
                'sw.website_id = s.website_id',
                ['websiteCode' => 'sw.code']
            )
            ->where('cpe.entity_id IN (?)', $productIds);

        $this->addIndexTableJoins($sql, $listOfTables);

        return $connection->query($sql);
    }

    /**
     * Add index table joins to the SQL query
     *
     * @param Select $sql
     * @param array $dimensionIndexTables
     * @return void
     */
    private function addIndexTableJoins(Select $sql, array $dimensionIndexTables): void
    {
        $cpiCombinedJoin = '(' . implode(
            ' UNION ALL ',
            array_map(
                static fn($tableName) => "SELECT * FROM $tableName",
                $dimensionIndexTables
            )
        ) . ')';
        $cpiCombinedExpr = new \Zend_Db_Expr("(
            SELECT
                product_id,
                store_id,
                customer_group_id,
                grant_catalog_category_view,
                grant_catalog_product_price,
                grant_checkout_items
            FROM $cpiCombinedJoin AS cpi_combined
        )");
        $sql->joinInner(
            ['cpi_combined' => $cpiCombinedExpr],
            'cpi_combined.product_id = cpe.entity_id AND ' . 'cpi_combined.store_id = s.store_id',
            [
                'productId' => 'cpi_combined.product_id',
                'customerGroupCode' => $this->expressionFactory->create([
                    'expression' => 'SHA1(cpi_combined.customer_group_id)',
                ]),
                'displayable' => 'cpi_combined.grant_catalog_category_view',
                'priceDisplayable' => 'cpi_combined.grant_catalog_product_price',
                'addToCartAllowed' => 'cpi_combined.grant_checkout_items'
            ]
        );
    }
}
