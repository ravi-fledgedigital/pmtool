<?php

namespace Cpss\Crm\Block\Adminhtml\Order\Creditmemo;

class Totals extends \Magento\Sales\Block\Adminhtml\Order\Creditmemo\Totals
{
    /**
     * Initialize creditmemo totals array
     *
     * @return $this
     */
    protected function _initTotals()
    {
        parent::_initTotals();
        $order = $this->getSource();

        $this->_totals['grand_total'] = new \Magento\Framework\DataObject(
            [
                'code' => 'grand_total',
                'strong' => true,
                'value' => $order->getGrandTotal() < 0 ? 0 : $order->getGrandTotal(),
                'base_value' => $order->getBaseGrandTotal() < 0 ? 0 : $order->getBaseGrandTotal(),
                'label' => __('Grand Total'),
                'area' => 'footer',
            ]
        );
        return $this;
    }
}
