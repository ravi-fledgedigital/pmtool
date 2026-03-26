<?php
namespace WeltPixel\GA4\Model\ResourceModel\OrdersAddonPushed;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \WeltPixel\GA4\Model\OrdersAddonPushed::class,
            \WeltPixel\GA4\Model\ResourceModel\OrdersAddonPushed::class
        );
    }
} 