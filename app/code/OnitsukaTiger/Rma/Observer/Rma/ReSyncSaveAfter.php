<?php

declare(strict_types=1);

namespace OnitsukaTiger\Rma\Observer\Rma;

use Amasty\Rma\Model\Request\Repository as RmaRepository;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use OnitsukaTiger\Cegid\Model\Config;
use OnitsukaTiger\Cegid\Model\ResourceModel\ReturnAction\CollectionFactory;
use OnitsukaTiger\Logger\Logger;
use OnitsukaTiger\NetsuiteReturnOrderSync\Helper\Data;
use Magento\Framework\Event\Manager;
use Magento\Sales\Model\Order\Shipment;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping;

class ReSyncSaveAfter implements ObserverInterface
{
    const NEED_TO_SYNC = 0;

    const RMA_SAVE_SYNC_NETSUITE = 'rma_saved_sync_netsuite';

    const APPROVED_BY_ADMIN = 4;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var RmaRepository
     */
    protected RmaRepository $rmaRequestRepository;

    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * @var Data
     */
    protected Data $helperData;

    /**
     * @var ManagerInterface
     */
    protected ManagerInterface $messageManager;

    /**
     * @var \OnitsukaTiger\Rma\Helper\Data
     */
    protected \OnitsukaTiger\Rma\Helper\Data $helperRma;

    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $collectionFactory;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var Manager
     */
    protected Manager $eventManager;

    /**
     * @var Shipment
     */
    protected Shipment $shipmentModel;

    /**
     * @var StoreShipping
     */
    protected StoreShipping $storeShipping;

    /**
     * @param RequestInterface $request
     * @param Logger $logger
     * @param RmaRepository $rmaRequestRepository
     * @param ManagerInterface $messageManager
     * @param Data $helperData
     * @param \OnitsukaTiger\Rma\Helper\Data $helperRma
     * @param CollectionFactory $collectionFactory
     * @param Config $config
     * @param Manager $eventManager
     * @param Shipment $shipmentModel
     * @param StoreShipping $storeShipping
     */
    public function __construct(
        RequestInterface                                   $request,
        Logger                                             $logger,
        RmaRepository                                      $rmaRequestRepository,
        ManagerInterface                                   $messageManager,
        Data $helperData,
        \OnitsukaTiger\Rma\Helper\Data $helperRma,
        CollectionFactory   $collectionFactory,
        Config                  $config,
        Manager $eventManager,
        Shipment $shipmentModel,
        StoreShipping $storeShipping
    ) {
        $this->request = $request;
        $this->logger = $logger;
        $this->rmaRequestRepository = $rmaRequestRepository;
        $this->messageManager = $messageManager;
        $this->helperData = $helperData;
        $this->helperRma = $helperRma;
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->shipmentModel = $shipmentModel;
        $this->storeShipping = $storeShipping;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Amasty\Rma\Api\Data\RequestInterface $request */
        $request = $observer->getData('request');
        if ($this->request->getParam('is_sync')) {
            try {
                //Resync to NS
                $request->setData('netsuite_internal_rma_request', self::NEED_TO_SYNC);
                $request->setData('status', self::APPROVED_BY_ADMIN);
                $model = $this->rmaRequestRepository->save($request);
                $this->logger->info(sprintf("We updated resync RMA %s to Netsuite", $request->getRequestId()));

                //Resync to Cegid
                $rmaRequestSyncCollection = $this->collectionFactory->create()
                ->addFieldToFilter("request_id", $request->getRequestId());
                if ($rmaRequestSyncCollection->getItems()) {
                    foreach ($rmaRequestSyncCollection->getItems() as $item) {
                        $item->delete();
                        $this->logger->info(sprintf("We updated resync RMA %s to Cegid", $request->getRequestId()));
                    }
                }
                $shipment = $this->shipmentModel->loadByIncrementId($request->getShipmentIncrementId());
                $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();

                if ($this->storeShipping->isShippingFromWareHouse($sourceCode)) {
                    $this->eventManager->dispatch(
                        self::RMA_SAVE_SYNC_NETSUITE,
                        ['request' => $model]
                    );
                }
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    sprintf("We can\'t update RMA %s, Error: %s", $request->getRequestId(), $e->getMessage())
                );
                $this->logger->error(sprintf("We can\'t update RMA %s, Error: %s", $request->getRequestId(), $e->getMessage()));
            }
        }
    }
}
