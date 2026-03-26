<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Request;

use Amasty\Rma\Api\CreateReturnProcessorInterface;
use Amasty\Rma\Api\CustomerRequestRepositoryInterface;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\GuestCreateRequestProcessInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Model\OptionSource\State;
use Amasty\Rma\Model\Validation\Customer\CustomerIdValidator;
use Amasty\Rma\Observer\RmaEventNames;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class CustomerRequestRepository implements CustomerRequestRepositoryInterface
{
    public function __construct(
        private readonly RequestRepositoryInterface $requestRepository,
        private readonly ResourceModel\Request $requestResource,
        private readonly GuestCreateRequestProcessInterface $guestCreateRequestProcess,
        private readonly StatusRepositoryInterface $statusRepository,
        private readonly CreateReturnProcessorInterface $createReturnProcessor,
        private readonly ManagerInterface $eventManager,
        private ?CustomerIdValidator $customerIdValidator = null
    ) {
        $this->customerIdValidator ??= ObjectManager::getInstance()->get(CustomerIdValidator::class);
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
        } elseif (!$this->customerIdValidator->isValid(
            (int)$returnOrder->getOrder()->getCustomerId(),
            (int)$request->getCustomerId()
        )) {
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
        $this->requestRepository->save($request);
        $this->eventManager->dispatch(RmaEventNames::RMA_CREATED_BY_CUSTOMER, ['request' => $request]);

        return $request;
    }

    /**
     * @inheritDoc
     */
    public function getById($requestId, $customerId)
    {
        $request = $this->requestRepository->getById((int)$requestId);
        if ($request->getCustomerId() !== (int)$customerId) {
            throw new NoSuchEntityException(__('Request doesn\'t exsists'));
        }

        return $request;
    }

    /**
     * @inheritDoc
     */
    public function getByHash($hash)
    {
        if (!($requestId = $this->requestResource->getRequestIdByHash($hash))) {
            throw new NoSuchEntityException(__('Request doesn\'t exsists'));
        }
        $request = $this->requestRepository->getById((int)$requestId);

        return $request;
    }

    public function closeRequest($requestIdHash, $customerId = 0)
    {
        if (is_string($requestIdHash)) {
            $request = $this->getByHash($requestIdHash);
        } else {
            $request = $this->getById($requestIdHash, $customerId);
        }

        if ($request) {
            $this->eventManager->dispatch(
                \Amasty\Rma\Observer\RmaEventNames::RMA_CANCELED,
                ['request' => $request]
            );

            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function saveTracking($hash, \Amasty\Rma\Api\Data\TrackingInterface $tracking)
    {
        $request = $this->getByHash($hash);

        if ($request) {
            $tracking->setRequestId($request->getRequestId())
                ->setIsCustomer(true);
            $this->requestRepository->saveTracking($tracking);

            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function removeTracking($hash, $trackingId)
    {
        $request = $this->getByHash($hash);

        if ($request) {

            $tracking = $this->requestRepository->getTrackingById($trackingId);
            if ($tracking->getRequestId() === $request->getRequestId() && $tracking->isCustomer()) {
                $this->requestRepository->deleteTrackingById($trackingId);

                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function saveRating($hash, $rating, $ratingComment)
    {
        if ($rating && $rating > 0 && $rating < 6) {
            try {
                $request = $this->getByHash($hash);
                $status = $this->statusRepository->getById($request->getStatus());

                if (!$request->getRating() && $status->getState() === State::RESOLVED) {
                    $request->setRating($rating)
                        ->setRatingComment($ratingComment);
                    $this->requestRepository->save($request);

                    return true;
                }
            } catch (\Exception $e) {
                null;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getEmptyRequestModel()
    {
        return $this->requestRepository->getEmptyRequestModel();
    }

    /**
     * @inheritDoc
     */
    public function getEmptyRequestItemModel()
    {
        return $this->requestRepository->getEmptyRequestItemModel();
    }

    /**
     * @inheritDoc
     */
    public function getEmptyTrackingModel()
    {
        return $this->requestRepository->getEmptyTrackingModel();
    }
}
