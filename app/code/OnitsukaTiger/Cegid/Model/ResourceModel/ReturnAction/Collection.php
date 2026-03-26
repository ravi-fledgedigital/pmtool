<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Cegid\Model\ResourceModel\ReturnAction;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'returnaction_id';


    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \OnitsukaTiger\Cegid\Model\ReturnAction::class,
            \OnitsukaTiger\Cegid\Model\ResourceModel\ReturnAction::class
        );
    }
}
