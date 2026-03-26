<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Phrase;
use Magento\User\Model\User;

class SwitchButton extends Template
{

    /**
     * SwitchButton constructor.
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        array $data = []
    )
    {
        parent::__construct($context, $data);
    }

    /**
     * @return Template
     */
    public function _prepareLayout()
    {
        $this->addChild(
            'submit_button',
            \Magento\Backend\Block\Widget\Button::class,
            [
                'id' => 'switch_button',
                'label' => $this->getButtonLabel(),
                'class' => 'switch-button',
                'onclick' => 'setLocation(\'' . $this->getSwitchUrl() . '\')'
            ]
        );
        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getSwitchUrl()
    {
        if ($this->getRequest()->getParam('source_code')) {
            return $this->getUrl('store_shipping/rma/manage', ['manager_code' => $this->getRequest()->getParam('source_code')]);
        }
        if ($this->getRequest()->getParam('manager_code')) {
            return $this->getUrl('store_shipping/shipment/index', ['source_code' => $this->getRequest()->getParam('manager_code')]);
        }
    }

    /**
     * @return Phrase
     */
    public function getButtonLabel()
    {
        if ($this->getRequest()->getParam('source_code')) {
            return __('Switch to RMA Management');
        }
        if ($this->getRequest()->getParam('manager_code')) {
            return __('Switch to Shipment Management');
        }
    }

}
