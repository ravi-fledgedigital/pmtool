<?php
namespace OnitsukaTigerKorea\Rma\Model\ResourceModel;

class ReturnInfo extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('return_xml_tracking_no_info', 'entity_id');
    }

}
