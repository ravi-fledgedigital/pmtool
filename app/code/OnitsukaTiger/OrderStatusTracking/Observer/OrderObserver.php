<?php
namespace OnitsukaTiger\OrderStatusTracking\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use OnitsukaTiger\OrderStatusTracking\Model\ResourceModel\Order\Status\TrackingFactory as TrackingResourceFactory;
use OnitsukaTiger\OrderStatusTracking\Model\Order\Status\TrackingFactory;

class OrderObserver implements ObserverInterface
{
    /**
     * @var TrackingFactory
     */
    private $trackingFactory;

    /**
     * @var TrackingResourceFactory
     */
    private $trackingResourceFactory;

    /**
     * OrderObserver constructor.
     * @param TrackingFactory $trackingFactory
     * @param TrackingResourceFactory $trackingResourceFactory
     */
    public function __construct(
        TrackingFactory $trackingFactory,
        TrackingResourceFactory $trackingResourceFactory
    ) {
        $this->trackingFactory = $trackingFactory;
        $this->trackingResourceFactory = $trackingResourceFactory;
    }

    /**
     * @param Observer $observer
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        $tracking = $this->trackingFactory->create();
        $tracking->setStatus($order->getStatus());
        $tracking->setParentId($order->getId());

        $this->trackingResourceFactory
            ->create()
            ->save($tracking);
    }
}
