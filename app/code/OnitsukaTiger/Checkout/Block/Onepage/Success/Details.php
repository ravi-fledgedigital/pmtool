<?php

namespace OnitsukaTiger\Checkout\Block\Onepage\Success;

use Magento\Framework\View\Element\Template;

class Details extends Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    public function __construct(
        Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Session $session,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->session = $session;
        $this->_orderFactory = $orderFactory;
    }

    protected function _prepareLayout()
    {
        if (!$this->registry->registry('current_order')) {
            $this->registry->register('current_order', $this->getOrder());
        }

        return parent::_prepareLayout();
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if ($this->session->getLastRealOrderId()){
            return $this->session->getLastRealOrder();
        }
        return $this->_orderFactory->create()->load($this->session->getLastOrderId());
    }
}
