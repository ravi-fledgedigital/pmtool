<?php

namespace OnitsukaTigerKorea\Rma\Plugin\Model\Request\DataProvider;

use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use OnitsukaTigerKorea\Rma\Helper\Data;
use Magento\Sales\Api\OrderItemRepositoryInterface;

class CreateForm
{

    /**
     * @var RequestInterface
     */
    protected $requestInterface;

    /**
     * @var $orderRepository
     */
    protected $orderRepository;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * DefaultColumn constructor.
     *
     * @param RequestInterface $requestInterface
     * @param OrderRepositoryInterface $orderRepository
     * @param Data $helperData
     * @param OrderItemRepositoryInterface $orderItemRepository
     */
    public function __construct(
        RequestInterface $requestInterface,
        OrderRepositoryInterface $orderRepository,
        Data $helperData,
        OrderItemRepositoryInterface $orderItemRepository
    )
    {
        $this->requestInterface = $requestInterface;
        $this->orderRepository = $orderRepository;
        $this->helperData = $helperData;
        $this->orderItemRepository = $orderItemRepository;
    }

    /**
     * @param \Amasty\Rma\Model\Request\DataProvider\CreateForm $subject
     * @param $result
     * @return array
     */
    public function afterGetData(\Amasty\Rma\Model\Request\DataProvider\CreateForm $subject, $result)
    {
        if ($orderId = $this->requestInterface->getParam('order_id')) {
            $order = $this->orderRepository->get($orderId);
            if ($this->helperData->enableShowProductSkuWms($order->getStoreId())) {
                foreach ($result as &$data) {
                    foreach ($data['return_items'] as &$items) {
                        foreach ($items as &$item) {
                            $orderItem = $this->orderItemRepository->get($item['order_item_id']);
                            /*$item['sku_wms'] = $orderItem->getSkuWms();*/
                            $product = $orderItem->getProduct();
                            $item['sku_wms'] = $product->getSkuWms();
                        }
                    }
                }
            }
        }
        return $result;
    }
}
