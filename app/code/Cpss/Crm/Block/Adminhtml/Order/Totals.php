<?php

namespace Cpss\Crm\Block\Adminhtml\Order;

use Cpss\Crm\Helper\Customer as CustomerHelper;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Helper\Admin;
use Cpss\Crm\Helper\Data;
use Magento\Store\Model\ScopeInterface;

class Totals extends \Magento\Sales\Block\Adminhtml\Order\Totals
{
    /**
     * @var CustomerHelper
     */
    protected $customerHelper;
    /**
     * @param Context $context
     * @param Registry $registry
     * @param Admin $adminHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Admin $adminHelper,
        CustomerHelper $customerHelper,
        array $data = []
    ) {
        $this->customerHelper = $customerHelper;
        parent::__construct($context, $registry, $adminHelper, $data);
    }

    /**
     * Initialize Point totals array
     *
     * @return $this
     */
    protected function _initTotals()
    {
        parent::_initTotals();
        $order = $this->getSource();
        if (!$this->_scopeConfig->getValue(Data::CRM_ENABLED_PATH, ScopeInterface::SCOPE_STORE, $order->getStore()->getStoreId())) {
            return $this;
        }
        $this->addTotalBefore(new \Magento\Framework\DataObject(
            [
                'code' => 'used_point',
                'strong' => false,
                'value' => $order->getUsedPoint() <  0 ? 0 : $order->getUsedPoint(),
                'label' => __('Used Point'),
            ]
        ), 'grand_total');
        $this->addTotalBefore(new \Magento\Framework\DataObject(
            [
                'code' => 'acquired_point',
                'strong' => false,
                'value' => $order->getAcquiredPoint() <  0 ? 0 : $order->getAcquiredPoint(),
                'label' => __('Points to be earned'),
            ]
        ), 'grand_total');
        return $this;
    }
}
