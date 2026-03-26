<?php
namespace Cpss\Crm\Model\ResourceModel;

class RealStore extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb {
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) 
    {
        parent::__construct($context);
    }
    
    protected function _construct()
    {
        $this->_init('crm_real_stores', 'entity_id');
    }


    /**
     * Load data by specified shopId
     *
     * @param string $shphId
     * @return array
     */
    public function loadByShopId($shopId)
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from($this->getMainTable())->where('shop_id=:shop_id');

        $binds = ['shop_id' => $shopId];

        return $connection->fetchRow($select, $binds);
    }
}