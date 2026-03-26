<?php
namespace OnitsukaTigerKorea\CategoryFilters\Model;

class CategoryFilters extends \Magento\Framework\Model\AbstractModel implements
    \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * @var const Cach_tag
     */
    public const CACHE_TAG = "onitsukatigerkorea_categoryfilters_categoryfilters";
    /**
     * @var $_cacheTag
     */
    protected $_cacheTag = "onitsukatigerkorea_categoryfilters_categoryfilters";
    /**
     * @var $eventPrefix
     */
    protected $_eventPrefix = "onitsukatigerkorea_categoryfilters_categoryfilters";

    /**
     * This is construct class for init
     *
     * @param _init
     */
    protected function _construct()
    {
        $this->_init("OnitsukaTigerKorea\CategoryFilters\Model\ResourceModel\CategoryFilters");
    }

    /**
     * This is get Identiities
     *
     * @return id
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . "_" . $this->getId()];
    }
    /**
     * This is get default values
     *
     * @return $value
     */
    public function getDefaultValues()
    {
        $values = [];
        return $values;
    }
}
