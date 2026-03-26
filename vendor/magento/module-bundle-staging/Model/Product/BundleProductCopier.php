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

namespace Magento\BundleStaging\Model\Product;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Zend_Db_Select_Exception;

/**
 * Class for Bundle product option copy
 */
class BundleProductCopier
{
    /**
     * AttributeCopier constructor.
     *
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly MetadataPool $metadataPool,
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Returns link id for requested update
     *
     * @param EntityMetadataInterface $metadata
     * @param int $entityId
     * @param int $createdIn
     * @return int
     * @throws Zend_Db_Select_Exception
     */
    private function getLinkId(EntityMetadataInterface $metadata, int $entityId, int $createdIn): int
    {
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from(['t' => $metadata->getEntityTable()], [$metadata->getLinkField()])
            ->where('t.' . $metadata->getIdentifierField() . ' = ?', $entityId)
            ->where('t.created_in <= ?', $createdIn)
            ->order('t.created_in DESC')
            ->limit(1)
            ->setPart('disable_staging_preview', true);
        return (int)$connection->fetchOne($select);
    }

    /**
     * Copy Bundle product option for staging
     *
     * @param int $fromRowId
     * @param int $toRowId
     * @return void
     * @throws Exception
     */
    private function copyBundleProductOption(int $fromRowId, int $toRowId): void
    {
        $connection = $this->getConnection();
        $bundleOptionTable = $this->resourceConnection->getTableName('catalog_product_bundle_option');
        $select = $connection->select()
            ->from($bundleOptionTable, '')
            ->where('parent_id = ?', $fromRowId);
        $insertColumns = [
            'option_id' => 'option_id',
            'required' => 'required',
            'position' => 'position',
            'type' => 'type',
            'parent_id' => new \Zend_Db_Expr($toRowId),
        ];
        $select->columns($insertColumns);
        $query = $select->insertFromSelect($bundleOptionTable, array_keys($insertColumns), true);
        $connection->query($query);
    }

    /**
     * Copy Bundle product selection for staging
     *
     * @param int $fromRowId
     * @param int $toRowId
     * @return void
     * @throws Exception
     */
    private function copyBundleProductSelection(int $fromRowId, int $toRowId): void
    {
        $connection = $this->getConnection();
        $bundleProductSelectionTable = $this->resourceConnection->getTableName('catalog_product_bundle_selection');
        $select = $connection->select()
            ->from($bundleProductSelectionTable, '')
            ->where('parent_product_id = ?', $fromRowId);
        $insertColumns = [
            'selection_id' => 'selection_id',
            'option_id' => 'option_id',
            'product_id' => 'product_id',
            'position' => 'position',
            'is_default' => 'is_default',
            'selection_price_type' => 'selection_price_type',
            'selection_price_value' => 'selection_price_value',
            'selection_qty' => 'selection_qty',
            'selection_can_change_qty' => 'selection_can_change_qty',
            'parent_product_id' => new \Zend_Db_Expr($toRowId),
        ];
        $select->columns($insertColumns);
        $query = $select->insertFromSelect($bundleProductSelectionTable, array_keys($insertColumns), true);
        $connection->query($query);
    }

    /**
     * Copy Bundle product relation for staging
     *
     * @param int $fromRowId
     * @param int $toRowId
     * @return void
     * @throws Exception
     */
    private function copyBundleProductRelation(int $fromRowId, int $toRowId): void
    {
        $connection = $this->getConnection();
        $bundleProductRelationTable = $this->resourceConnection->getTableName('catalog_product_relation');
        $select = $connection->select()
            ->from($bundleProductRelationTable, '')
            ->where('parent_id = ?', $fromRowId);
        $insertColumns = [
            'child_id' => 'child_id',
            'parent_id' => new \Zend_Db_Expr($toRowId),
        ];
        $select->columns($insertColumns);
        $query = $select->insertFromSelect($bundleProductRelationTable, array_keys($insertColumns), true);
        $connection->query($query);
    }

    /**
     * Copy Bundle product data for staging
     *
     * @param array $entityData
     * @param int $from
     * @param int $to
     * @return void
     * @throws Exception
     */
    public function copy(array $entityData, int $from, int $to): void
    {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $entityId = (int)$entityData[$metadata->getIdentifierField()];
        $fromRowId = $this->getLinkId($metadata, $entityId, $from);
        $toRowId = $this->getLinkId($metadata, $entityId, $to);

        $this->copyBundleProductOption($fromRowId, $toRowId);
        $this->copyBundleProductSelection($fromRowId, $toRowId);
        $this->copyBundleProductRelation($fromRowId, $toRowId);
    }

    /**
     * Return the connection to run the query
     *
     * @return AdapterInterface
     * @throws Exception
     */
    private function getConnection(): AdapterInterface
    {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        return $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
    }
}
