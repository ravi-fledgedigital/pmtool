<?php

namespace OnitsukaTigerKorea\Sales\Preference\Magento\Sales\Block\Adminhtml\Order\Creditmemo;

class View extends \Magento\Sales\Block\Adminhtml\Order\Creditmemo\View
{
    protected function _construct()
    {
        $this->_objectId = 'creditmemo_id';
        $this->_controller = 'adminhtml_order_creditmemo';
        $this->_mode = 'view';

        parent::_construct();

        $this->buttonList->remove('save');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');

        if (!$this->getCreditmemo()) {
            return;
        }

        if ($this->getCreditmemo()->canCancel()) {
            $this->buttonList->add(
                'cancel',
                [
                    'label' => __('Cancel'),
                    'class' => 'delete',
                    'onclick' => 'setLocation(\'' . $this->getCancelUrl() . '\')'
                ]
            );
        }

        if ($this->_isAllowedAction('Magento_Sales::emails')) {
            $this->addButton(
                'send_notification',
                [
                    'label' => __('Send Email'),
                    'class' => 'send-email',
                    'onclick' => 'confirmSetLocation(\'' . __(
                            'Are you sure you want to send a credit memo email to customer?'
                        ) . '\', \'' . $this->getEmailUrl() . '\')'
                ]
            );
        }

        if ($this->getCreditmemo()->canRefund()) {
            $this->buttonList->add(
                'refund',
                [
                    'label' => __('Refund'),
                    'class' => 'refund',
                    'onclick' => 'setLocation(\'' . $this->getRefundUrl() . '\')'
                ]
            );
        }

        if ($this->getCreditmemo()->canVoid()) {
            $this->buttonList->add(
                'void',
                [
                    'label' => __('Void'),
                    'class' => 'void',
                    'onclick' => 'setLocation(\'' . $this->getVoidUrl() . '\')'
                ]
            );
        }

        if ($this->getCreditmemo()->getId()) {
            $this->buttonList->add(
                'print',
                [
                    'label' => __('Print'),
                    'class' => 'print',
                    'onclick' => 'setLocation(\'' . $this->getPrintUrl() . '\')'
                ]
            );
        }

        if ($this->getCreditmemo()->getStoreId() == 5) {
            $this->buttonList->remove('print');
        }
    }
}
