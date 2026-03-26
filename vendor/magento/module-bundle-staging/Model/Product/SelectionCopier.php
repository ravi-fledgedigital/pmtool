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

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Class for Bundle product selection copy
 */
class SelectionCopier
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * AttributeCopier constructor.
     *
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        MetadataPool $metadataPool,
        ResourceConnection $resourceConnection
    ) {
        $this->metadataPool = $metadataPool;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Returns link id for requested update
     *
     * @param EntityMetadataInterface $metadata
     * @param int $entityId
     * @param int $createdIn
     * @return int
     * @throws \Zend_Db_Select_Exception
     */
    private function getLinkId(EntityMetadataInterface $metadata, int $entityId, int $createdIn): int
    {
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
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
     * Copy Bundle product selection for staging
     *
     * @param array $entityData
     * @param int $from
     * @param int $to
     * @return bool
     * @throws \Exception
     */
    public function copy(array $entityData, int $from, int $to): bool
    {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $entityId = (int)$entityData[$metadata->getIdentifierField()];
        $fromRowId = $this->getLinkId($metadata, $entityId, $from);
        $toRowId = $this->getLinkId($metadata, $entityId, $to);
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());

        $bundleSelectionPriceTable = $this->resourceConnection->getTableName('catalog_product_bundle_selection_price');
        $select = $connection->select()
            ->from($bundleSelectionPriceTable, '')
            ->where('parent_product_id = ?', $fromRowId);
        $insertColumns = [
            'selection_id' => 'selection_id',
            'website_id' => 'website_id',
            'selection_price_type' => 'selection_price_type',
            'selection_price_value' => 'selection_price_value',
            'parent_product_id' => new \Zend_Db_Expr($toRowId),
        ];
        $select->columns($insertColumns);
        $query = $select->insertFromSelect($bundleSelectionPriceTable, array_keys($insertColumns), true);
        $connection->query($query);

        return true;
    }
}
