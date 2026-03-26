<?php

namespace Cpss\Crm\Model\ResourceModel;

class ShopReceipt extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('sales_real_store_order', 'entity_id');
    }
}
