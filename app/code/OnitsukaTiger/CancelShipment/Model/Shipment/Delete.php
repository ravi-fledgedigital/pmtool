<?php
declare(strict_types=1);

namespace OnitsukaTiger\CancelShipment\Model\Shipment;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order\Shipment\Item;
use OnitsukaTiger\CancelShipment\Model\SourceDeductionProcessor;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\ShipmentCommentRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Delete
 * @package OnitsukaTiger\CancelShipment\Model\Shipment
 */
class Delete
{
    /**
     * @var SourceDeductionProcessor
     */
    private $sourceDeductionProcessor;

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var ShipmentCommentRepositoryInterface
     */
    private $shipmentCommentRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Delete constructor.
     * @param SourceDeductionProcessor $sourceDeductionProcessor
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentCommentRepositoryInterface $shipmentCommentRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        SourceDeductionProcessor $sourceDeductionProcessor,
        ShipmentRepositoryInterface $shipmentRepository,
        ShipmentCommentRepositoryInterface $shipmentCommentRepository,
        LoggerInterface $logger
    ) {
        $this->sourceDeductionProcessor = $sourceDeductionProcessor;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentCommentRepository = $shipmentCommentRepository;
        $this->logger = $logger;
    }

    /**
     * @param ShipmentInterface $shipment
     * @throws NoSuchEntityException
     */
    public function execute(ShipmentInterface $shipment, $sourceDeductionProcessor): void
    {
        if ($sourceDeductionProcessor) {
            $this->sourceDeductionProcessor->execute($shipment);
        }
        $this->setQtyShippedOrderItem($shipment);
        $this->shipmentRepository->delete($shipment);
        $this->logger->info('Delete Shipment:', $shipment->getData());
    }

    /**
     * Applying qty to order item
     * @param ShipmentInterface $shipment
     */
    private function setQtyShippedOrderItem(ShipmentInterface $shipment): void
    {
        /** @var Item $item */
        foreach ($shipment->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();

            $this->logger->info('Qty shipped before delete shipment:', ['sku' => $orderItem->getSku(), 'qty_shipped' => $orderItem->getQtyShipped()]);
            $orderItem->setQtyShipped($orderItem->getQtyShipped() - $item->getQty())->save();
            $this->logger->info('Qty shipped after delete shipment:', ['sku' => $orderItem->getSku(), 'qty_shipped' => $orderItem->getQtyShipped()]);
        }
    }
}
