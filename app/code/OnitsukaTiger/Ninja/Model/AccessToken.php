<?php

namespace OnitsukaTiger\Ninja\Model;

/**
 * AccessToken model
 */
class AccessToken extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'onitsukatiger_ninja_accesstoken';

    protected $_cacheTag = 'onitsukatiger_ninja_accesstoken';

    protected $_eventPrefix = 'onitsukatiger_ninja_accesstoken';

    /**
     * Initialize model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('OnitsukaTiger\Ninja\Model\ResourceModel\AccessToken');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
