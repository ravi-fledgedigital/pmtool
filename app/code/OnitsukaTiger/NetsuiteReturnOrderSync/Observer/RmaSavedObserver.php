<?php

namespace OnitsukaTiger\NetsuiteReturnOrderSync\Observer;

use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Model\OptionSource\State;
use Amasty\Rma\Api\Data\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use OnitsukaTiger\NetSuite\Model\SuiteTalk\ReturnOrder;

/**
 * Class RmaSavedObserver
 * @package OnitsukaTiger\NetsuiteReturnOrderSync\Observer
 */
class RmaSavedObserver implements ObserverInterface
{
    const NEED_TO_SYNC = 0;

    const SYNCED = 1;

    /**
     * @var StatusRepositoryInterface
     */
    private $statusRepository;

    /**
     * @var ReturnOrder
     */
    protected $returnOrder;

    /**
     * @var RequestRepositoryInterface
     */
    private $repository;

    /**
     * @var \OnitsukaTiger\NetsuiteReturnOrderSync\Helper\Data
     */
    protected $helperData;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var \OnitsukaTiger\Logger\Api\Logger
     */
    protected $logger;

    /**
     * RmaSavedObserver constructor.
     * @param StatusRepositoryInterface $statusRepository
     * @param RequestRepositoryInterface $repository
     * @param ReturnOrder $returnOrder
     * @param \OnitsukaTiger\NetsuiteReturnOrderSync\Helper\Data $helperData
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \OnitsukaTiger\Logger\Api\Logger $logger
     */
    public function __construct(
        StatusRepositoryInterface $statusRepository,
        RequestRepositoryInterface $repository,
        ReturnOrder $returnOrder,
        \OnitsukaTiger\NetsuiteReturnOrderSync\Helper\Data $helperData,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \OnitsukaTiger\Logger\Api\Logger $logger
    )
    {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->statusRepository = $statusRepository;
        $this->returnOrder = $returnOrder;
        $this->repository = $repository;
        $this->helperData = $helperData;
        $this->logger = $logger;
    }

    /**
     * Sync cancel order to NetSuite
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var RequestInterface $request */
        $request = $observer->getData('request');
        $status = $this->statusRepository->getById($request->getStatus());

        // Check on System Config Store -> Config -> OnitsukaTiger -> RMA to Netsuite -> enable
        if($this->helperData->getGeneralConfig('enabled', $request->getStoreId())){
            // if request approved and not sync to Netsuite
            if (($status->getState() === State::AUTHORIZED) &&
                ($request->getData('netsuite_internal_rma_request') == self::NEED_TO_SYNC)) {
                /** @var \NetSuite\Classes\UpdateResponse $statusResponse */
                try {
                    $updateResponse = $this->returnOrder->execute($request);
                    if ($updateResponse && $updateResponse->writeResponse->status->isSuccess) {
                        // update netsuite internal id of rma request
                        $model = $this->repository->getById($request->getRequestId());
                        $model->setData('netsuite_internal_rma_request', self::SYNCED);
                        $this->repository->save($model);
                    } elseif ($updateResponse) {
                        $this->backToOriginalStatus($request);
                    }
                } catch (\Exception $e) {
                    $this->messageManager->addExceptionMessage(
                        $e,
                        sprintf("We can\'t update RMA %s, Error: %s", $request->getRequestId(), $e->getMessage())
                    );
                    $this->logger->error(sprintf("We can\'t update RMA %s, Error: %s", $request->getRequestId(), $e->getMessage()));
                    $this->backToOriginalStatus($request);
                }
            }
        }
    }

    /**
     * @param RequestInterface $request
     *
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function backToOriginalStatus(RequestInterface $request){
        $model = $this->repository->getById($request->getRequestId());
        $model->setStatus($request->getOrigData('status'));
        $this->repository->save($model);
    }
}
