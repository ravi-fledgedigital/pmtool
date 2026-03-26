<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepBase\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Wishlist\Model\ResourceModel\Item as ItemResource;

class Wishlist
{
    private ItemResource $itemResource;

    public function __construct(ItemResource $itemResource)
    {
        $this->itemResource = $itemResource;
    }

    /**
     * @param int $wishlistId
     * @return array|string[]
     * @throws LocalizedException
     */
    public function getWishlistItems(int $wishlistId): array
    {
        return $this->getWishlistsItems([$wishlistId]);
    }

    /**
     * @param int[] $wishlistsIds
     * @return string[][]
     * @throws LocalizedException
     */
    public function getWishlistsItems(array $wishlistsIds): array
    {
        $select = $this->itemResource->getConnection()->select();
        $select->from(['main_table' => $this->itemResource->getMainTable()], ['wishlist_id', 'wishlist_item_id']);
        $select->where('wishlist_id IN (?)', $wishlistsIds);

        $productTable = $this->itemResource->getConnection()->getTableName('catalog_product_entity');

        $select->joinLeft(
            ['product' => $productTable],
            'main_table.product_id = product.entity_id',
            'sku'
        );

        $itemOptionTable = $this->itemResource->getConnection()->getTableName('wishlist_item_option');
        $select->joinLeft(
            ['item_option' => $itemOptionTable],
            "main_table.wishlist_item_id = item_option.wishlist_item_id AND item_option.code = 'simple_product'",
            []
        );
        $select->joinLeft(
            ['child_product' => $productTable],
            'item_option.product_id = child_product.entity_id',
            ['child_sku' => 'child_product.sku']
        );

        $storeTable = $this->itemResource->getConnection()->getTableName('store');

        $select->joinLeft(
            ['store' => $storeTable],
            'main_table.store_id = store.store_id',
            ['store_code' => 'store.code']
        );

        $select->order('wishlist_item_id DESC');

        return $this->itemResource->getConnection()->fetchAll($select);
    }
}
