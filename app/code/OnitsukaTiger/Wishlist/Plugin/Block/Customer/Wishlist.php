<?php
/**
 * PluginWishlist
 */

namespace OnitsukaTiger\Wishlist\Plugin\Block\Customer;

use Magento\Wishlist\Block\Customer\Wishlist as CustomerWishlist;

class Wishlist extends CustomerWishlist
{
    /**
     * Add wishlist conditions to collection
     *
     * @param  \Magento\Wishlist\Model\ResourceModel\Item\Collection $collection
     * @return \Magento\Wishlist\Block\Customer\Wishlist
     */
    protected function _prepareCollection($collection)
    {
        $collection->setInStockFilter(false)->setOrder('added_at', 'ASC');
        return $this;
    }
}
