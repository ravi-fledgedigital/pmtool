<?php

namespace OnitsukaTigerIndo\RmaAccount\Block;

use Amasty\Rma\Model\Request\ResourceModel\CollectionFactory as RmaCollectionFactory;
use Magento\Framework\App\Response\Http;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\App\Http\Context as HttpContext;

/**
 * RmaAccount content block
 */
class RmaAccount extends Template
{
    /**
     * @var RmaCollectionFactory
     */
    private RmaCollectionFactory $collectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    private OrderCollectionFactory $orderCollectionFactory;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var Http
     */
    private Http $redirect;

    /**
     * Construct Method
     *
     * @param Context $context
     * @param HttpContext $httpContext
     * @param RmaCollectionFactory $collectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param Http $redirect
     * @param array $data
     */
    public function __construct(
        Context                $context,
        HttpContext            $httpContext,
        RmaCollectionFactory   $collectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        Http $redirect,
        array                  $data = []
    ) {
        parent::__construct($context, $data);
        $this->httpContext = $httpContext;
        $this->collectionFactory = $collectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->redirect = $redirect;
    }

    /**
     * Get Rma Account Details
     *
     * @return bool
     */
    public function getRmaAccountDetails()
    {
        $customerId = $this->getCustomerId();
        $requestCollection = $this->collectionFactory->create();
        $requestCollection->addFieldToFilter('customer_id', $customerId);
        $requestCollection->load();
        $rmadata = [];
        $rmaDataStatus = [];
        foreach ($requestCollection as $requestItem) {
            $rmaDataStatus[] = $requestItem->getStatus();
            $rmadata[] = $requestItem->getOrderId();
        }

        $orderId = [];
        $orderCollection = $this->orderCollectionFactory->create()->addFieldToFilter('customer_id', $customerId);
        foreach ($orderCollection as $order) {
            $orderId[] = $order->getId();
        }
        $ids = array_intersect($rmadata, $orderId);
        if (!empty($ids) && in_array(4, $rmaDataStatus)) {
            return true;
        }
    }

    /**
     * Get Customer Id
     *
     * @return mixed|null
     */
    public function getCustomerId()
    {
        return $this->httpContext->getValue('customer_id');
    }

    /**
     * Get OrderId
     *
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->getRequest()->getParam('order_id');
    }
}
