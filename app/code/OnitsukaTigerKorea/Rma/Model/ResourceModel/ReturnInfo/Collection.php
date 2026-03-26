<?php
namespace OnitsukaTigerKorea\Rma\Model\ResourceModel\ReturnInfo;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('OnitsukaTigerKorea\Rma\Model\ReturnInfo', 'OnitsukaTigerKorea\Rma\Model\ResourceModel\ReturnInfo');
    }

}
