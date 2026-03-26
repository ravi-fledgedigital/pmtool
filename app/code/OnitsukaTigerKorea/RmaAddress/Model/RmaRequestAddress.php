<?php

namespace OnitsukaTigerKorea\RmaAddress\Model;

class RmaRequestAddress extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\OnitsukaTigerKorea\RmaAddress\Model\ResourceModel\RmaRequestAddress::class);
    }
}
