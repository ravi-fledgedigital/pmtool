<?php
declare(strict_types=1);

namespace OnitsukaTiger\CancelShipment\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use OnitsukaTiger\CancelShipment\Model\Shipment\Cancel as ShipmentCancel;
use OnitsukaTiger\Logger\Api\Logger;
use Magento\Framework\Event\ManagerInterface;

/**
 * Class Cancel
 * @package OnitsukaTiger\CancelShipment\Controller\Adminhtml\Shipment
 */
class Cancel extends Action
{
    /**
     * @var ShipmentCancel
     */
    private $shipmentCancel;

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * Cancel constructor.
     * @param Action\Context $context
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentCancel $shipmentCancel
     * @param Logger $logger
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        Action\Context $context,
        ShipmentRepositoryInterface $shipmentRepository,
        ShipmentCancel $shipmentCancel,
        Logger $logger,
        ManagerInterface $eventManager
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentCancel = $shipmentCancel;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
        parent::__construct($context);
    }

    public function execute()
    {
        $comeFrom = $this->getRequest()->getParam('come_from');
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        $isPartialCancel = $this->getRequest()->getParam('is_partial_cancel');
        $shipment = $this->shipmentRepository->get($shipmentId);

        try {
            $this->shipmentCancel->execute($shipment, $isPartialCancel);
            $this->messageManager->addSuccessMessage(__('Successfully canceled shipment #%1.', $shipment->getIncrementId()));

            // dispatch event
            $this->logger->info(sprintf('Process after delete shipment id[%s]', $shipment->getIncrementId()));
            $this->eventManager->dispatch(\OnitsukaTiger\CancelShipment\Model\Shipment\Cancel::AFTER_CANCEL_SHIPMENT,['shipment' => $shipment]);

        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error cancel shipment %s. Error: %s', $shipment->getIncrementId(), $e->getMessage()));
            $this->messageManager->addErrorMessage(__('Error cancel shipment #%1.', $shipment->getIncrementId()));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        if ($comeFrom) {
            $resultRedirect->setPath('sales/shipment/index');
            return $resultRedirect;
        }
        $resultRedirect->setPath('sales/order/view', ['order_id' => $shipment->getOrderId(), 'active_tab' => 'order_shipments']);

        return $resultRedirect;
    }
}
