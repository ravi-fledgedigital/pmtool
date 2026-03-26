<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use OnitsukaTiger\Logger\Logger;
use OnitsukaTiger\NetSuite\Api\NetSuiteInterface;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping;
use OnitsukaTiger\NetSuiteStoreShipping\Model\Validation;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order\Shipment;

class RecoverPacked extends Action
{
    const ADMIN_RESOURCE = 'Magento_Sales::ship_recover_pack';
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private ShipmentRepositoryInterface $shipmentRepository;
    private LoggerInterface $commonLogger;
    private Logger $logger;
    private ManagerInterface $eventManager;
    private StoreShipping $storeShipping;
    private ShipmentStatus $shipmentStatusModel;
    private OrderStatus $orderStatusModel;
    /**
     * @var bool
     */
    protected $isShop = false;
    private \OnitsukaTiger\NetSuiteStoreShipping\Model\Validation $validation;
    private Shipment $shipment;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param LoggerInterface $commonLogger
     * @param Logger $logger
     * @param ManagerInterface $eventManager
     * @param StoreShipping $storeShipping
     * @param ShipmentStatus $shipmentStatusModel
     * @param OrderStatus $orderStatusModel
     * @param \OnitsukaTiger\NetSuiteStoreShipping\Model\Validation $validation
     * @param Shipment $shipment
     * @param Context $context
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ShipmentRepositoryInterface $shipmentRepository,
        LoggerInterface $commonLogger,
        Logger  $logger,
        ManagerInterface $eventManager,
        StoreShipping $storeShipping,
        ShipmentStatus $shipmentStatusModel,
        OrderStatus $orderStatusModel,
        Validation  $validation,
        Shipment    $shipment,
        Context $context
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentRepository = $shipmentRepository;
        $this->commonLogger = $commonLogger;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
        $this->storeShipping = $storeShipping;
        $this->shipmentStatusModel = $shipmentStatusModel;
        $this->orderStatusModel = $orderStatusModel;
        $this->validation = $validation;
        $this->shipment = $shipment;
        parent::__construct($context);
    }
    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/recoverPacked.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $recoverDataArray = [];

        $shipmentId = $this->getRequest()->getParam("shipment_increment_id");
        $shipment = $this->shipment->loadByIncrementId($shipmentId);
        $fulfillmentId = $this->getRequest()->getParam("fulfillment_id");
        $url = $this->getUrl('sales/shipment/view', ['shipment_id' => $shipment->getId()]);
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($url);

        $recoverDataArray['Shipment ID'] = $shipmentId;
        $recoverDataArray['Fulfillment ID'] = $fulfillmentId;
        $recoverDataArray['URL'] = $url;

        if (!$fulfillmentId) {
            $this->messageManager->addErrorMessage(__('The fulfillment_id value is empty, please check value again!'));
            return $resultRedirect;
        }

        try {
            $shipmentRepository = $this->shipmentRepository->get($shipment->getId());
            $result = $this->validation->validateShipment($shipmentRepository, [ShipmentStatus::STATUS_PROCESSING, ShipmentStatus::STATUS_PREPACKED]);
            $recoverDataArray['Validate Shipment Result'] = $result;
            $order = $shipmentRepository->getOrder();
            /*$this->shipmentStatusModel->updateStatus($shipmentRepository, ShipmentStatus::STATUS_PREPACKED);
            $this->orderStatusModel->setOrderStatus($order);*/
            $this->setShipmentStatusToPacked($shipmentRepository);
            $this->setOrderStatusToPacked($order);
            $this->eventManager->dispatch(NetSuiteInterface::EVENT_UPDATE_ORDER_STATUS_PREPACKED, ['shipment' => $shipmentRepository]);
            $this->messageManager->addSuccessMessage(__('Successfully Recover Packed'));
            $logger->info('Recover Array: ' . print_r($recoverDataArray, true));
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error Recover Packed shipment.'));
            $this->logger->error(sprintf('Error Recover Packed shipment. [%s]. Message: [%s]', $shipmentRepository->getIncrementId(), $e->getMessage()));
            return $resultRedirect;
        }
    }

    private function setOrderStatusToPacked($order)
    {
        $order->setStatus('packed');
        $order->addStatusToHistory($order->getStatus(), 'Order packed successfully by admin user.');
        $order->save();
    }

    private function setShipmentStatusToPacked($shipment)
    {
        $shipment->getExtensionAttributes()->setStatus('packed');
        try {
            $result = $this->shipmentRepository->save($shipment);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $result = null;
        }
        return $result;
    }
}