<?php

namespace Firebear\PlatformNetsuite\Model\Import;

use Firebear\ImportExport\Model\Import\Order\Item;

class OrderItem extends Item
{
    /**
     * @param array $rowData
     * @return bool|int|string
     */
    protected function _getExistEntityId(array $rowData)
    {
        if (isset($rowData['is_from_ns']) && $rowData['is_from_ns']) {
            $bind = [
                ':sku' => $rowData[self::COLUMN_SKU],
                ':order_id' => $this->_getOrderId($rowData),
                ':order_item_id' => $rowData['item_id']
            ];
            $select = $this->_connection->select();
            $select->from($this->getMainTable(), 'item_id')
                ->where('sku = :sku')
                ->where('order_id = :order_id')
                ->where('item_id = :order_item_id');
            $itemId = $this->_connection->fetchOne($select, $bind);
            if ($itemId) {
                return $itemId;
            }
        }
        return parent::_getEntityId($rowData);
    }
}
