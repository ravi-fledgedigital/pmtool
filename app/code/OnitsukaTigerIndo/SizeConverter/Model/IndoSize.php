<?php

namespace OnitsukaTigerIndo\SizeConverter\Model;

class IndoSize extends \Magento\Framework\Model\AbstractModel
{
    const CACHE_TAG = 'onitsukatigerindo_sizeconverter';

    protected $_cacheTag = 'onitsukatigerindo_sizeconverter';

    protected $_eventPrefix = 'onitsukatigerindo_sizeconverter';

    /**
     * Initialize customer model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(\OnitsukaTigerIndo\SizeConverter\Model\ResourceModel\IndoSize::class);
    }
}
