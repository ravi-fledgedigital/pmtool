<?php

namespace OnitsukaTiger\Rma\Block\Widget\Grid\Column\Renderer;

use Magento\Framework\DataObject;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\Text;
use Magento\Backend\Block\Context;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderStatus extends Text
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        array $data = [])
    {
        $this->orderRepository = $orderRepository;
        parent::__construct($context, $data);
    }

    /**
     * @param DataObject $row
     * @return array|mixed|string|void|null
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $order = $this->getOrder($row->getParentId());
        $label = $row->setOrder($order)->getStatusLabel();

        if(empty($label)){
            $label = $order->getStatusLabel();
        }
        return $label;
    }

    /**
     * @param $id
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrder($id)
    {
        return $this->orderRepository->get($id);
    }
}
