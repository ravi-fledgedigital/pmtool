<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Forward;
use Magento\Framework\App\ResponseInterface;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Store\Model\StoreManagerInterface;
use OnitsukaTiger\CancelShipment\Model\Shipment\Cancel as ShipmentCancel;
use OnitsukaTiger\Logger\StoreShipping\Logger;
use OnitsukaTiger\NetSuite\Model\NetSuite;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping;

class Delete extends Action
{
    /**
     * @var ShipmentRepository
     */
    protected $shipmentRepository;

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var NetSuite
     */
    protected $netSuiteManager;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var ShipmentCancel
     */
    protected $shipmentCancel;

    /**
     * Pack constructor.
     * @param Context $context
     * @param ForwardFactory $resultForwardFactory
     * @param ShipmentRepository $shipmentRepository
     * @param StoreManagerInterface $storeManager
     * @param NetSuite $netSuiteManager
     * @param Logger $logger
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param ShipmentCancel $shipmentCancel
     */
    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory,
        ShipmentRepository $shipmentRepository,
        StoreManagerInterface $storeManager,
        NetSuite $netSuiteManager,
        Logger $logger,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        ShipmentCancel $shipmentCancel
    )
    {
        $this->storeManager = $storeManager;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->messageManager = $context->getMessageManager();
        $this->netSuiteManager = $netSuiteManager;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
        $this->shipmentCancel = $shipmentCancel;
        parent::__construct($context);
    }

    /**
     * @return Forward|ResponseInterface|Redirect|ResultInterface
     * @throws NoSuchEntityException
     * @throws InputException
     */
    public function execute()
    {
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        $comeForm = $this->getRequest()->getParam('come_from');
        if ($shipmentId) {
            $shipment = $this->shipmentRepository->get($shipmentId);
            if ($shipment) {
                try {
                    $this->shipmentCancel->execute($shipment);
                    $this->messageManager->addSuccessMessage(__('Successfully Delete shipment #%1.', $shipment->getIncrementId()));
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage(__('Error Delete shipment #%1.', $shipment->getIncrementId()));
                    $this->logger->error(sprintf('SPS: Error delete shipment [%s]. Message: [%s]', $shipment->getIncrementId(), $e->getMessage()));
                }

                $resultRedirect = $this->resultRedirectFactory->create();
                if (!$comeForm) {
                    $resultRedirect->setPath('sales/order/view', ['order_id' => $shipment->getOrderId(), 'active_tab' => 'order_shipments']);
                    return $resultRedirect;
                }

                $resultRedirect->setPath('sales/shipment/index');
                if ($comeForm === StoreShipping::STORE_SHIPPING_ROUTE) {
                    $resultRedirect->setPath('store_shipping/shipment/index', ['source_code' => $shipment->getExtensionAttributes()->getSourceCode()]);
                }
                return $resultRedirect;
            }
        } else {
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }
    }

}
