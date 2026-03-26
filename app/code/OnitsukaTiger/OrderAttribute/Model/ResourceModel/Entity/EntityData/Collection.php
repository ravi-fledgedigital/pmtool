<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\EntityData;

/**
 * @method \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity getResource()
 */
class Collection extends \Magento\Eav\Model\Entity\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    protected function _construct()
    {
        $this->_init(
            \OnitsukaTiger\OrderAttribute\Model\Entity\EntityData::class,
            \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity::class
        );
    }
}
