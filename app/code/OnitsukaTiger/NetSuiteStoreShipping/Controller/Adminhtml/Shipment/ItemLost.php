<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\Shipment;
use OnitsukaTiger\Logger\Logger;

class ItemLost extends Action
{
    /**
     * @var ShipmentRepositoryInterface
     */
    private ShipmentRepositoryInterface $shipmentRepository;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var Shipment
     */
    private Shipment $shipment;

    /**
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param Logger $logger
     * @param Shipment $shipment
     * @param Context $context
     * @param \OnitsukaTiger\NetSuite\Model\SuiteTalk\UpdateShipmentStatusToNetsuite $updateShipmentStatusToNetsuite
     * @param SourceRepositoryInterface $sourceRepository
     * @param \OnitsukaTiger\Cegid\Model\UpdateShipmentStatusToCegid $updateShipmentStatusToCegid
     */
    public function __construct(
        ShipmentRepositoryInterface $shipmentRepository,
        Logger  $logger,
        Shipment    $shipment,
        Context $context,
        private \OnitsukaTiger\NetSuite\Model\SuiteTalk\UpdateShipmentStatusToNetsuite $updateShipmentStatusToNetsuite,
        private SourceRepositoryInterface $sourceRepository,
        private \OnitsukaTiger\Cegid\Model\UpdateShipmentStatusToCegid  $updateShipmentStatusToCegid
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->logger = $logger;
        $this->shipment = $shipment;
        parent::__construct($context);
    }
    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/itemLost.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $shipmentId = $this->getRequest()->getParam("shipment_id");
        $shipment = $this->shipment->loadByIncrementId($shipmentId);
        $url = $this->getUrl('sales/shipment/view', ['shipment_id' => $shipment->getId()]);
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($url);

        if (!$shipment) {
            $this->messageManager->addErrorMessage(__('The request shipment not exist. please check value again!'));
            return $resultRedirect;
        }

        $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
        try {
            $source = $this->sourceRepository->get($sourceCode);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('Assign source no longer exist.'));
            return $resultRedirect;
        }

        try {
            $shipmentRepository = $this->shipmentRepository->get($shipment->getId());
            $this->setShipmentStatusToItemLost($shipmentRepository);
            $this->setOrderStatusToItemLost($shipmentRepository->getOrder());
            if ($source && $source->getIsShippingFromStore()) {
                $this->updateShipmentStatusToCegid->execute($shipmentRepository, 'Item Lost');
            } else {
                $this->updateShipmentStatusToNetsuite->execute($shipment, "Item Lost");
            }
            $this->messageManager->addSuccessMessage(__('Successfully update status to item lost'));
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Item lost status not updated for shipment. please check value again!'));
            $this->logger->error(sprintf('Error Item Lost shipment. [%s]. Message: [%s]', $shipmentRepository->getIncrementId(), $e->getMessage()));
            $logger->info("Error Message: " . $e->getMessage());
            return $resultRedirect;
        }
    }

    private function setOrderStatusToItemLost($order)
    {
        $shipments = $order->getShipmentsCollection();
        $orderShipmentCount = $shipments->getSize();
        $shipmentCount = 0;
        foreach ($shipments as $shipment) {
            $shipmentRepository = $this->shipmentRepository->get($shipment->getId());
            if ($shipmentRepository->getExtensionAttributes()->getStatus() == 'item_lost') {
                $shipmentCount++;
            }
        }

        if ($orderShipmentCount == $shipmentCount) {
            $order->setStatus('item_lost');
            $order->addStatusToHistory($order->getStatus(), 'Order status change to item lost');
            $order->save();
        }
    }

    private function setShipmentStatusToItemLost($shipment)
    {
        $shipment->getExtensionAttributes()->setStatus('item_lost');
        try {
            $result = $this->shipmentRepository->save($shipment);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $result = null;
        }
        return $result;
    }
}
