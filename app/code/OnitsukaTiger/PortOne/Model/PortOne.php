<?php
namespace OnitsukaTiger\PortOne\Model;

use Magento\Framework\Model\AbstractModel;

class PortOne extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\OnitsukaTiger\PortOne\Model\ResourceModel\PortOne::class);
    }
}
