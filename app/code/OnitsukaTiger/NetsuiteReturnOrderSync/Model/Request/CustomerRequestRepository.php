<?php

namespace OnitsukaTiger\NetsuiteReturnOrderSync\Model\Request;

use Amasty\Rma\Api\CreateReturnProcessorInterface;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Observer\RmaEventNames;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Amasty\Rma\Api\GuestCreateRequestProcessInterface;
use OnitsukaTiger\NetsuiteReturnOrderSync\Helper\Data;

class CustomerRequestRepository extends \Amasty\Rma\Model\Request\CustomerRequestRepository
{

    /**
     * @var RequestRepositoryInterface
     */
    private $requestRepository;

    /**
     * @var CreateReturnProcessorInterface
     */
    private $createReturnProcessor;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var GuestCreateRequestProcessInterface
     */
    private $guestCreateRequestProcess;

    /**
     * @var Data
     */
    protected $rmaHelper;

    /**
     * CustomerRequestRepository constructor.
     * @param Data $rmaHelper
     * @param RequestRepositoryInterface $requestRepository
     * @param \Amasty\Rma\Model\Request\ResourceModel\Request $requestResource
     * @param GuestCreateRequestProcessInterface $guestCreateRequestProcess
     * @param StatusRepositoryInterface $statusRepository
     * @param CreateReturnProcessorInterface $createReturnProcessor
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        Data $rmaHelper,
        RequestRepositoryInterface $requestRepository,
        \Amasty\Rma\Model\Request\ResourceModel\Request $requestResource,
        GuestCreateRequestProcessInterface $guestCreateRequestProcess,
        StatusRepositoryInterface $statusRepository,
        CreateReturnProcessorInterface $createReturnProcessor,
        ManagerInterface $eventManager
    )
    {
        $this->requestRepository = $requestRepository;
        $this->eventManager = $eventManager;
        $this->rmaHelper = $rmaHelper;
        $this->createReturnProcessor = $createReturnProcessor;
        $this->guestCreateRequestProcess = $guestCreateRequestProcess;
        parent::__construct(
            $requestRepository,
            $requestResource,
            $guestCreateRequestProcess,
            $statusRepository,
            $createReturnProcessor,
            $eventManager
        );
    }

    public function create(RequestInterface $request, $secretKey = ''): RequestInterface
    {
        if (!($returnOrder = $this->createReturnProcessor->process($request->getOrderId()))) {
            throw new CouldNotSaveException(__('Wrong Order.'));
        }

        if ($secretKey) {
            if ($orderId = $this->guestCreateRequestProcess->getOrderIdBySecretKey($secretKey)) {
                if ((int)$orderId !== (int)$returnOrder->getOrder()->getEntityId()) {
                    throw new CouldNotSaveException(__('Wrong Order'));
                }
            } else {
                throw new CouldNotSaveException(__('Order not found'));
            }
        } elseif ($returnOrder->getOrder()->getCustomerId() != $request->getCustomerId()) {
            throw new CouldNotSaveException(__('Wrong Customer Id'));
        }

        $requestItems = $request->getRequestItems();
        $returnOrderItems = $returnOrder->getItems();
        $resultItems = [];
        foreach ($requestItems as $requestItem) {
            $item = false;
            foreach ($returnOrderItems as $returnOrderItem) {
                if ($returnOrderItem->getItem()->getItemId() == $requestItem->getOrderItemId()) {
                    $item = $returnOrderItem;
                    break;
                }
            }

            if ($item && $item->isReturnable() && $requestItem->getQty() <= $item->getAvailableQty()
                && isset($item->getResolutions()[$requestItem->getResolutionId()])
            ) {
                $requestItem->setRequestQty($requestItem->getQty());
                $resultItems[] = $requestItem;
            }
        }
        if (empty($resultItems)) {
            throw new CouldNotSaveException(__('Items were not selected'));
        }
        $request->setRequestItems($resultItems);

        $this->eventManager->dispatch(RmaEventNames::BEFORE_CREATE_RMA_BY_CUSTOMER, ['request' => $request]);
        $rmaAlgorithmEnabled = $this->rmaHelper->getRmaAlgorithmConfig('enabled', $request->getStoreId());
        if (!$rmaAlgorithmEnabled) {
            $this->requestRepository->save($request);
            $this->eventManager->dispatch(RmaEventNames::RMA_CREATED_BY_CUSTOMER, ['request' => $request]);
        }
        return $request;
    }
}
