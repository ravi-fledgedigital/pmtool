<?php

namespace OnitsukaTigerKorea\Rma\Plugin\Model\Request\DataProvider;

use Magento\Framework\App\RequestInterface;
use OnitsukaTigerKorea\Rma\Helper\Data;
use Magento\Sales\Api\OrderItemRepositoryInterface;

class Form
{

    /**
     * @var RequestInterface
     */
    protected $requestInterface;

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
     * @param Data $helperData
     * @param OrderItemRepositoryInterface $orderItemRepository
     */
    public function __construct(
        RequestInterface $requestInterface,
        Data $helperData,
        OrderItemRepositoryInterface $orderItemRepository
    )
    {
        $this->requestInterface = $requestInterface;
        $this->helperData = $helperData;
        $this->orderItemRepository = $orderItemRepository;
    }

    /**
     * @param \Amasty\Rma\Model\Request\DataProvider\Form $subject
     * @param $result
     * @return array
     */
    public function afterGetData(\Amasty\Rma\Model\Request\DataProvider\Form $subject, $result)
    {
        if ($requestId = $this->requestInterface->getParam('request_id')) {
            $storeId = $result[$requestId]['store_id'];
            if ($this->helperData->enableShowProductSkuWms($storeId)) {
                foreach ($result[$requestId]['return_items'] as &$items) {
                    foreach ($items as &$item) {
                        $orderItem = $this->orderItemRepository->get($item['order_item_id']);
                        /*$item['sku_wms'] = $orderItem->getSkuWms();*/
                        $product = $orderItem->getProduct();
                        $item['sku_wms'] = $product->getSkuWms();
                    }
                }
            }
        }
        return $result;
    }
}
