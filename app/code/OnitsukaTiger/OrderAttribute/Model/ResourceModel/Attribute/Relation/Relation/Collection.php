<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\Relation;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \OnitsukaTiger\OrderAttribute\Model\Attribute\Relation\Relation::class,
            \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\Relation::class
        );
    }
}
