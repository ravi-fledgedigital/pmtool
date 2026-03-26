<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Block\Adminhtml\Attribute\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{

    protected function _construct()
    {
        parent::_construct();
        $this->setId('attribute_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Attribute Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab(
            'main',
            [
                'label'   => __('Attribute Configuration'),
                'title'   => __('Attribute Configuration'),
                'content' => $this->getChildHtml('main'),
                'active'  => true
            ]
        );
        $this->addTab(
            'labels',
            [
                'label'   => __('Title, Tooltip, Options'),
                'title'   => __('Title, Tooltip, Options'),
                'content' => $this->getChildHtml('options')
            ]
        );
        $this->addTab(
            'conditions',
            [
                'label'   => __('Shipping Methods'),
                'title'   => __('Shipping Methods'),
                'content' => $this->getChildHtml('conditions')
            ]
        );

        return parent::_beforeToHtml();
    }
}
