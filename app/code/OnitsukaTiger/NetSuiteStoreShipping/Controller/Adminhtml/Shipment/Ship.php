<?php
namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Forward;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Store\Model\StoreManagerInterface;
use OnitsukaTiger\Logger\StoreShipping\Logger;
use OnitsukaTiger\NetSuite\Model\NetSuite;

class Ship extends Action
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
    protected $eventManger;

    /**
     * Pack constructor.
     * @param Context $context
     * @param ForwardFactory $resultForwardFactory
     * @param ShipmentRepository $shipmentRepository
     * @param StoreManagerInterface $storeManager
     * @param NetSuite $netSuiteManager
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory,
        ShipmentRepository $shipmentRepository,
        StoreManagerInterface $storeManager,
        NetSuite $netSuiteManager,
        Logger $logger,
        \Magento\Framework\Event\ManagerInterface $eventManger
    ) {
        $this->storeManager = $storeManager;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->messageManager = $context->getMessageManager();
        $this->netSuiteManager = $netSuiteManager;
        $this->logger = $logger;
        $this->eventManger = $eventManger;
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
        if ($shipmentId) {
            $shipment = $this->shipmentRepository->get($shipmentId);
            if ($shipment) {
                try {
                    $result = $this->netSuiteManager->setIsShop()->orderShipped($shipment->getIncrementId());
                    if ($result) {
                        $this->messageManager->addSuccessMessage(__('Successfully Ship shipment #%1.', $shipment->getIncrementId()));
                        $this->eventManger->dispatch('sales_order_shipment_ship_save_after', ['shipment' => $shipment]);
                    }
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage(__('Error Ship shipment #%1.', $shipment->getIncrementId()));
                    $this->logger->error(sprintf('SPS: Error Ship shipment [%s]. Message: [%s]', $shipment->getIncrementId(), $e->getMessage()));
                }

                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath(
                    'store_shipping/shipment/view',
                    [
                        'shipment_id' => $shipment->getEntityId()
                    ]
                );

                return $resultRedirect;
            }
        } else {
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }
    }
}
