<?php
namespace WeltPixel\GA4\Controller\Track;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderRepository;


class Purchase extends Action
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\PurchaseBuilderInterface */
    protected $purchaseBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @param Context $context
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \WeltPixel\GA4\Api\ServerSide\Events\PurchaseBuilderInterface $purchaseBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        Context $context,
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \WeltPixel\GA4\Api\ServerSide\Events\PurchaseBuilderInterface $purchaseBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi,
        OrderRepository $orderRepository
    ) {
        parent::__construct($context);
        $this->ga4Helper = $ga4Helper;
        $this->purchaseBuilder = $purchaseBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getPostValue('orderId', false);

        $result = [
            'success' => true
        ];

        try {
            $order = $this->orderRepository->get($orderId);
            $purchaseEvent = $this->purchaseBuilder->getPurchaseEvent($order);
            $this->ga4ServerSideApi->pushPurchaseEvent($purchaseEvent);
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['error'] = $e->getMessage();
        }

        return $this->prepareResult($result);
    }

    /**
     * @param array $result
     * @return string
     */
    protected function prepareResult($result)
    {
        $jsonData = json_encode($result);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }
}
