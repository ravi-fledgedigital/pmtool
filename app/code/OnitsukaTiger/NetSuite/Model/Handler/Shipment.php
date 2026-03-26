<?php


namespace OnitsukaTiger\NetSuite\Model\Handler;

use Exception;
use Magento\Framework\Exception\InputException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use OnitsukaTiger\Logger\Api\Logger;
use OnitsukaTiger\NetSuite\Api\Queue\CancelMessageInterface;
use OnitsukaTiger\NetSuite\Model\SuiteTalk\CancelOrder;

class Shipment
{
    /**
     * @var PublisherInterface
     */
    protected $publisher;
    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;
    /**
     * @var CancelOrder
     */
    protected $cancel;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Shipment constructor.
     * @param PublisherInterface $publisher
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param Logger $logger
     * @param CancelOrder $cancel
     */
    public function __construct(
        PublisherInterface $publisher,
        ShipmentRepositoryInterface $shipmentRepository,
        Logger $logger,
        CancelOrder $cancel
    )
    {
        $this->publisher = $publisher;
        $this->shipmentRepository = $shipmentRepository;
        $this->cancel = $cancel;
        $this->logger = $logger;
    }

    /**
     * @param CancelMessageInterface $message
     * @throws InputException
     */
    public function cancel(
        CancelMessageInterface $message
    )
    {
        $this->logger->info(sprintf('Begin Queue Cancel Shipment id run: %s', $message->getShipmentId()));
        try {
            $this->logger->info('Sync Cancel Shipment to Netsuite');
            $this->cancel->cancel($message->getShipmentId(), $message->getStoreId(), $message->getSourceCode());
        } catch (Exception $e) {
            if ($message->getRetry() < 3) {
                $this->logger->err(sprintf('retry : %s error : %s', $message->getRetry() + 1, $e->getMessage()));
                $message->setRetry($message->getRetry() + 1);
                $this->publisher->publish(
                    CancelMessageInterface::TOPIC_NAME,
                    $message
                );
//                $retry = new \OnitsukaTiger\NetSuite\Model\Handler\Retry($this->publisher, $message);
//                $retry->start();
                return;
            }
            $this->logger->err(sprintf('retry failed : %s', $e->getMessage()));
            throw $e;
        }
    }
}
