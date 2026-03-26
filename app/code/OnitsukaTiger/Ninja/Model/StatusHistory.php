<?php

namespace OnitsukaTiger\Ninja\Model;

/**
 * StatusHistory model
 */
class StatusHistory extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'onitsukatiger_ninja_statushistory';

    protected $_cacheTag = 'onitsukatiger_ninja_statushistory';

    protected $_eventPrefix = 'onitsukatiger_ninja_statushistory';

    /**
     * Initialize model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('OnitsukaTiger\Ninja\Model\ResourceModel\StatusHistory');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
