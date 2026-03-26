<?php
namespace OnitsukaTiger\PortOne\Block\Order\View;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;
use OnitsukaTiger\PortOne\Model\ResourceModel\PortOne\Collection as PortOneCollection;

class Info extends Template
{
    protected $registry;

    protected $portoneCollection;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        PortOneCollection $portoneCollection,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->portoneCollection = $portoneCollection;
        parent::__construct($context, $data);
    }

    /**
     * @return Order|null
     */
    public function getOrder()
    {
        return $this->registry->registry('current_order');
    }

    public function getPortOneData()
    {
        $order = $this->getOrder();

        if (!$order || !$order->getId()) {
            return null;
        }

        return $this->portoneCollection
            ->addFieldToSelect(['order_entity_id', 'payment_id', 'txid'])
            ->addFieldToFilter('order_entity_id', $order->getId())
            ->getFirstItem();
    }
}
