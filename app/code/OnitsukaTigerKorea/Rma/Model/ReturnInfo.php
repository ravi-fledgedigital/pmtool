<?php
namespace OnitsukaTigerKorea\Rma\Model;

class ReturnInfo extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{

    const CACHE_TAG = 'return_xml_tracking_no_info';

    /**
     * @var string
     */
    protected $_cacheTag = 'return_xml_tracking_no_info';

    /**
     * @var string
     */
    protected $_eventPrefix = 'return_xml_tracking_no_info';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('OnitsukaTigerKorea\Rma\Model\ResourceModel\ReturnInfo');
    }

    /**
     * @return string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @return array
     */
    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }
}
