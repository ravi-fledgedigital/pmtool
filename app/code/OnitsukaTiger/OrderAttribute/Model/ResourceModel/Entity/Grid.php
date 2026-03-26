<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity;

use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutEntityInterface;

class Grid extends \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\EntityData\Collection
{
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->addFieldToFilter(
            CheckoutEntityInterface::PARENT_ENTITY_TYPE,
            CheckoutEntityInterface::ENTITY_TYPE_ORDER
        );
        $this->getSelect()->group($this->getIdFieldName());
    }
}
