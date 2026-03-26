<?php

namespace OnitsukaTiger\OrderStatusTracking\Helper;

use OnitsukaTiger\OrderStatusTracking\Model\ResourceModel\Order\Status\Tracking\Collection;
use Psr\Log\LoggerInterface;

/**
 * Class Data
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $statusTrackingCollection;

    /**
     * @var \OnitsukaTiger\Logger\Logger
     */
    protected $logger;

    const STATUS_TRACKING_DESCRIPTION = [
        'pending'=>'Your order has been placed.',
        'processing'=>'Your order has been confirmed.',
        'complete'=>'Your order has been completed.',
        ];


    /**
     * Data constructor.
     * @param Collection $statusTrackingCollection
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \OnitsukaTiger\Logger\Logger $logger
     */
    public function __construct(
        Collection $statusTrackingCollection,
        \Magento\Framework\App\Helper\Context $context,
        \OnitsukaTiger\Logger\Logger $logger
    ) {
        $this->statusTrackingCollection = $statusTrackingCollection;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @param $order
     * @return Collection
     */
    public function getStatusTracking($order) {
        try {
            return $this->statusTrackingCollection->getByOrderId($order->getId());
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * @param $status
     * @return mixed
     */
    public function getStatusDescription($status){
        if (array_key_exists($status, self::STATUS_TRACKING_DESCRIPTION)) {
            return self::STATUS_TRACKING_DESCRIPTION[$status];
        }
        return '';
    }

    /**
     * @param $order
     * @param $status
     * @return Collection
     */
    public function getStatusTrackingDate($order,$status){
        try {
            return $this->statusTrackingCollection->getByOrderIdStatus($order->getId(),$status);
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }
}
