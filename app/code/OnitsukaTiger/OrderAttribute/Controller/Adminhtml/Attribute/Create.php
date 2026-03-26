<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Attribute;

class Create extends \OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Attribute
{
    /**
     * @see \OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Attribute\Edit::execute
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        return $this->_forward('edit');
    }
}
