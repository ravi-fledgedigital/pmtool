<?php

namespace OnitsukaTigerIndo\RmaAccount\Controller\Adminhtml\Items;

class NewAction extends \OnitsukaTigerIndo\RmaAccount\Controller\Adminhtml\Items
{

    /**
     * New Action
     *
     * @return void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
