<?php

namespace OnitsukaTiger\PreOrders\Ui\Component\Listing\Order\Column;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class IsPreOrder extends Column
{
    protected $_orderRepository;
    protected $collectionFactory;
 
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        CollectionFactory $collectionFactory,
        array $components = [], array $data = [])
    {
        $this->_orderRepository = $orderRepository;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
 
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $order  = $this->_orderRepository->get($item["entity_id"]);
                $order_id = $order->getEntityId();
                $collection = $this->collectionFactory->create();
                $collection->addFieldToFilter('entity_id',$order_id);
                $data = $collection->getFirstItem();
                $item[$this->getData('name')] = $data->getIsPreOrder();
            }
        }
        return $dataSource;
    }
}