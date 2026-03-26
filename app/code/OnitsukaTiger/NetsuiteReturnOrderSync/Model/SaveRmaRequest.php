<?php
namespace OnitsukaTiger\NetsuiteReturnOrderSync\Model;

use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Observer\RmaEventNames;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use OnitsukaTiger\Logger\Api\Logger;
use Magento\Framework\App\RequestInterface as AppRequest;

class SaveRmaRequest {

    /**
     * @var RequestRepositoryInterface
     */
    protected $requestRepository;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var AppRequest
     */
    protected $appRequest;

    /**
     * @var \OnitsukaTigerKorea\Rma\Helper\Data
     */
    protected $koreaRmaHelper;

    /**
     * SaveRmaRequest constructor.
     *
     * @param RequestRepositoryInterface $requestRepository
     * @param ManagerInterface $eventManager
     * @param Logger $logger
     * @param AppRequest $request
     * @param \OnitsukaTigerKorea\Rma\Helper\Data $koreaRmaHelper
     */
    public function __construct(
        RequestRepositoryInterface $requestRepository,
        ManagerInterface $eventManager,
        Logger $logger,
        AppRequest $request,
        \OnitsukaTigerKorea\Rma\Helper\Data $koreaRmaHelper
    )
    {
        $this->logger = $logger;
        $this->requestRepository = $requestRepository;
        $this->eventManager = $eventManager;
        $this->appRequest = $request;
        $this->koreaRmaHelper = $koreaRmaHelper;
    }

    /**
     * @param $requests
     */
    public function execute($requests){
        foreach ($requests as $request) {
            try {
                $this->requestRepository->save($request);
                if($request->getStoreId() == \OnitsukaTiger\Store\Model\Store::KO_KR) {
                    $initStatusRmaKorea = $this->koreaRmaHelper->getInitialStatusId($request->getStoreId());
                    $request->setStatus($initStatusRmaKorea);

                    $initResolutionRmaKorea = $this->koreaRmaHelper->getInitialResolutionId($request->getStoreId());
                    $requestItems = $request->getRequestItems();
                    foreach($requestItems as $requestItem){
                        $requestItem->setResolutionId($initResolutionRmaKorea);
                    }
                }
                if ($this->appRequest->getActionName() === 'manage') {
                    $this->eventManager->dispatch(RmaEventNames::RMA_CREATED_BY_MANAGER, ['request' => $request]);
                }else {
                    $this->eventManager->dispatch(RmaEventNames::RMA_CREATED_BY_CUSTOMER, ['request' => $request]);
                }
            } catch (CouldNotSaveException $e) {
                $this->logger->debug($e->getMessage());
            }
        }
    }
}
