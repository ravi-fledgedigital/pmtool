<?php

namespace OnitsukaTiger\Ninja\Model;

/**
 * AccessToken model
 */
class Order extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'onitsukatiger_ninja_order';

    protected $_cacheTag = 'onitsukatiger_ninja_order';

    protected $_eventPrefix = 'onitsukatiger_ninja_order';

    /**
     * Initialize model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('OnitsukaTiger\Ninja\Model\ResourceModel\Order');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
