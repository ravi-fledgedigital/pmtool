<?php
namespace OnitsukaTiger\PortOne\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class PortOne extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('portone', 'id');
    }
}
