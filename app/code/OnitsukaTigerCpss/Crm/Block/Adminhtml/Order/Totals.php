<?php

namespace OnitsukaTigerCpss\Crm\Block\Adminhtml\Order;
use Cpss\Crm\Helper\Data;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Helper\Admin;
use OnitsukaTigerCpss\Crm\Helper\Data as CustomerHelper;
use Magento\Store\Model\ScopeInterface;

class Totals extends \Magento\Sales\Block\Adminhtml\Order\Totals
{
    /**
     * @var CustomerHelper
     */
    protected $customerHelper;
    public function __construct(
        Context $context,
        Registry $registry,
        Admin $adminHelper,
        CustomerHelper $customerHelper,
        array $data = [])
    {
        $this->customerHelper = $customerHelper;
        parent::__construct($context, $registry, $adminHelper, $data);
    }

    /**
     * Initialize creditmemo totals array
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
                'value' => (empty($order->getUsedPoint()) || $order->getUsedPoint() <  0) ? 0 : number_format($order->getUsedPoint()),
                'label' => __('Used Point'),
            ]
        ), 'grand_total');
        $this->addTotalBefore(new \Magento\Framework\DataObject(
            [
                'code' => 'acquired_point',
                'strong' => false,
                'value' => (empty($order->getAcquiredPoint()) || $order->getAcquiredPoint() <  0) ? 0 : number_format($order->getAcquiredPoint()),
                'label' => __('Points to be earned:'),
            ]
        ), 'grand_total');
        return $this;
    }
}
