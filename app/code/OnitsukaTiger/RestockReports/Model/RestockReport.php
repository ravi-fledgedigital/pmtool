<?php

namespace OnitsukaTiger\RestockReports\Model;

class RestockReport extends \Magento\Framework\Model\AbstractModel
{
    public const CACHE_TAG = 'restock_queue';

    /**
     * @var $_cacheTag
     */
    protected $_cacheTag = 'restock_queue';

    /**
     * @var $_eventPrefix
     */
    protected $_eventPrefix = 'restock_queue';

    /**
     * Initialize customer model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(\OnitsukaTiger\RestockReports\Model\ResourceModel\RestockReport::class);
    }
}
