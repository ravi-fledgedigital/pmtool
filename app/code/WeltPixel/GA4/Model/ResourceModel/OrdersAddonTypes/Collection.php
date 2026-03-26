<?php
namespace WeltPixel\GA4\Model\ResourceModel\OrdersAddonTypes;

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
            \WeltPixel\GA4\Model\OrdersAddonTypes::class,
            \WeltPixel\GA4\Model\ResourceModel\OrdersAddonTypes::class
        );
    }
} 