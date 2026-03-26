<?php

namespace OnitsukaTigerIndo\RmaAccount\Model;

use Magento\Framework\Model\AbstractModel;

class RmaAccount extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init(\OnitsukaTigerIndo\RmaAccount\Model\ResourceModel\RmaAccount::class);
    }
}
