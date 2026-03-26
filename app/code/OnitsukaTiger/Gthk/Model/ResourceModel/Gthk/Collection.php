<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Gthk\Model\ResourceModel\Gthk;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'gthk_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \OnitsukaTiger\Gthk\Model\Gthk::class,
            \OnitsukaTiger\Gthk\Model\ResourceModel\Gthk::class
        );
    }
}

