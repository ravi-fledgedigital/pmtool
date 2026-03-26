<?php
namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\Shipment;

class AdminAddComment extends AbstractAddComment
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Sales::shipment';

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }

    /***
     * @param bool $checkIsShippingFromStore
     */
    public function execute($checkIsShippingFromStore = false)
    {
        parent::execute($checkIsShippingFromStore);
    }
}
