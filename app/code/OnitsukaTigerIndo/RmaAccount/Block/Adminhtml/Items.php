<?php

namespace OnitsukaTigerIndo\RmaAccount\Block\Adminhtml;

class Items extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'items';
        $this->_headerText = __('RMA Account Details');
        $this->_addButtonLabel = __('Add New Details Form');
        parent::_construct();
    }
}
