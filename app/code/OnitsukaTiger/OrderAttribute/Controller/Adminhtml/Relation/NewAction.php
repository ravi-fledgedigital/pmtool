<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Relation;

class NewAction extends \OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Relation
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
