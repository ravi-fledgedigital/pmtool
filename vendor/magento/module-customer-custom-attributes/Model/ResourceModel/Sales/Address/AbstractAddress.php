<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Address;

use Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\AbstractSales;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

/**
 * Customer Sales Address abstract resource
 */
abstract class AbstractAddress extends AbstractSales implements ResetAfterRequestInterface
{
    /**
     * Used us prefix to name of column table
     *
     * @var null | string
     */
    protected $_columnPrefix = null;

    /**
     * Data caching to avoid multiple DB queries
     *
     * @var array
     */
    private $cachedData = [];

    /**
     * Attach data to models
     *
     * @param \Magento\Framework\DataObject[] $entities
     * @return $this
     */
    public function attachDataToEntities(array $entities)
    {
        $items = [];
        $itemIds = [];
        foreach ($entities as $item) {
            /** @var $item \Magento\Framework\DataObject */
            $id = $item->getId();
            $items[$id] = $item;
            if (!isset($this->cachedData[$id])) {
                $this->cachedData[$id] = [];
                $itemIds[] = $id;
            }
        }

        if ($itemIds) {
            $this->fetchData($itemIds);
        }

        foreach ($this->cachedData as $id => $row) {
            if (!isset($items[$id])) {
                continue;
            }
            $items[$id]->addData($row);
        }

        return $this;
    }

    /**
     * Query DB when no data to be attached for $itemIds is found in $cachedData
     *
     * @param array $itemIds
     */
    private function fetchData(array $itemIds)
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable()
        )->where(
            "{$this->getIdFieldName()} IN (?)",
            $itemIds
        );
        $rowSet = $this->getConnection()->fetchAll($select);
        foreach ($rowSet as $row) {
            $this->cachedData[$row[$this->getIdFieldName()]] = $row;
        }
    }

    /**
     * @inheritdoc
     */
    public function _resetState(): void
    {
        $this->cachedData = [];
    }
}
