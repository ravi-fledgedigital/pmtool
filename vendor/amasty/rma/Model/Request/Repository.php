<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Request;

use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\Data\RequestItemInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Observer\RmaEventNames;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class Repository implements RequestRepositoryInterface
{
    /**
     * @var \Amasty\Rma\Api\Data\RequestInterfaceFactory
     */
    private $requestFactory;

    /**
     * @var \Amasty\Rma\Api\Data\RequestItemInterfaceFactory
     */
    private $requestItemFactory;

    /**
     * @var \Amasty\Rma\Api\Data\TrackingInterfaceFactory
     */
    private $trackingFactory;

    /**
     * @var ResourceModel\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ResourceModel\RequestItemCollectionFactory
     */
    private $requestItemCollectionFactory;

    /**
     * @var ResourceModel\TrackingCollectionFactory
     */
    private $trackingCollectionFactory;

    /**
     * @var ResourceModel\Request
     */
    private $requestResource;

    /**
     * @var ResourceModel\RequestItem
     */
    private $requestItemResource;

    /**
     * @var \Amasty\Rma\Api\Data\RequestInterface[]
     */
    private $requests;

    /**
     * @var StatusRepositoryInterface
     */
    private $statusRepository;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var ResourceModel\Tracking
     */
    private $trackingResource;

    /**
     * @var \Magento\Framework\Math\Random
     */
    private $mathRandom;

    public function __construct(
        \Amasty\Rma\Api\Data\RequestInterfaceFactory $requestFactory,
        \Amasty\Rma\Api\Data\RequestItemInterfaceFactory $requestItemFactory,
        \Amasty\Rma\Api\Data\TrackingInterfaceFactory $trackingFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Amasty\Rma\Api\StatusRepositoryInterface $statusRepository,
        \Amasty\Rma\Model\Request\ResourceModel\Request $requestResource,
        \Amasty\Rma\Model\Request\ResourceModel\Tracking $trackingResource,
        \Amasty\Rma\Model\Request\ResourceModel\RequestItem $requestItemResource,
        \Amasty\Rma\Model\Request\ResourceModel\CollectionFactory $collectionFactory,
        \Amasty\Rma\Model\Request\ResourceModel\RequestItemCollectionFactory $requestItemCollectionFactory,
        \Amasty\Rma\Model\Request\ResourceModel\TrackingCollectionFactory $trackingCollectionFactory,
        \Amasty\Rma\Model\History\CreateEvent $createEvent,
        \Magento\Framework\Math\Random $mathRandom
    ) {
        $this->requestFactory = $requestFactory;
        $this->requestItemFactory = $requestItemFactory;
        $this->trackingFactory = $trackingFactory;
        $this->collectionFactory = $collectionFactory;
        $this->requestItemCollectionFactory = $requestItemCollectionFactory;
        $this->trackingCollectionFactory = $trackingCollectionFactory;
        $this->requestResource = $requestResource;
        $this->requestItemResource = $requestItemResource;
        $this->statusRepository = $statusRepository;
        $this->eventManager = $eventManager;
        $this->trackingResource = $trackingResource;
        $this->mathRandom = $mathRandom;
    }

    /**
     * @inheritdoc
     */
    public function getById($requestId)
    {
        if (!isset($this->requests[$requestId])) {
            /** @var \Amasty\Rma\Api\Data\RequestInterface $request */
            $request = $this->requestFactory->create();
            $this->requestResource->load($request, $requestId);
            if (!$request->getRequestId()) {
                throw new NoSuchEntityException(__('Request with specified ID "%1" not found.', $requestId));
            }
            /** @var ResourceModel\RequestItemCollection $requestItemCollection */
            $requestItemCollection = $this->requestItemCollectionFactory->create();
            $requestItemCollection->addFieldToFilter(
                RequestItemInterface::REQUEST_ID,
                $request->getRequestId()
            )->addOrder(RequestItemInterface::REQUEST_ITEM_ID, Collection::SORT_ORDER_ASC)
            ->addOrder(RequestItemInterface::ORDER_ITEM_ID, Collection::SORT_ORDER_ASC);
            $request->setRequestItems($requestItemCollection->getItems());
            /** @var ResourceModel\TrackingCollection $trackingCollection */
            $trackingCollection = $this->trackingCollectionFactory->create();
            $trackingCollection->addFieldToFilter(
                RequestItemInterface::REQUEST_ID,
                $request->getRequestId()
            );
            $request->setTrackingNumbers($trackingCollection->getItems());

            $this->requests[$requestId] = $request;
        }

        return $this->requests[$requestId];
    }

    /**
     * @inheritDoc
     */
    public function getByHash($hash)
    {
        if (!($requestId = $this->requestResource->getRequestIdByHash($hash))) {
            throw new NoSuchEntityException(__('Request doesn\'t exsists'));
        }
        $request = $this->getById((int)$requestId);

        return $request;
    }

    /**
     * @inheritdoc
     */
    public function save(RequestInterface $request)
    {
        try {
            if ($request->getRequestId()) {
                $request = $this->getById($request->getRequestId())->addData($request->getData());
            } else {
                $request->setUrlHash($this->mathRandom->getUniqueHash());
            }

            if (!$request->getStatus()) {
                $request->setStatus($this->statusRepository->getInitialStatusId());
            }

            $this->requestResource->save($request);

            $requestItemIds = [];
            foreach ($request->getRequestItems() as $item) {
                $item->setRequestId($request->getRequestId());
                $this->requestItemResource->save($item);
                $requestItemIds[] = $item->getRequestItemId();
            }
            $this->requestItemResource->removeDeletedItems($request->getRequestId(), $requestItemIds);

            $origRating = $request->getOrigData(RequestInterface::RATING);
            if (!$origRating && $request->getRating()) {
                $this->eventManager->dispatch(
                    RmaEventNames::RMA_RATED,
                    ['request' => $request]
                );
            }

            $origStatus = (int)$request->getOrigData(RequestInterface::STATUS);

            if ($origStatus !== 0 && $origStatus !== $request->getStatus()) {
                $this->eventManager->dispatch(
                    RmaEventNames::STATUS_CHANGED,
                    ['request' => $request, 'original_status' => $origStatus, 'new_status' => $request->getStatus()]
                );
            }

            unset($this->requests[$request->getRequestId()]);
        } catch (\Exception $e) {
            if ($request->getRequestId()) {
                throw new CouldNotSaveException(
                    __(
                        'Unable to save request with ID %1. Error: %2',
                        [$request->getRequestId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotSaveException(__('Unable to save new request. Error: %1', $e->getMessage()));
        }

        return $request;
    }

    /**
     * @inheritdoc
     */
    public function saveTracking(\Amasty\Rma\Api\Data\TrackingInterface $tracking)
    {
        try {
            $this->trackingResource->save($tracking);
            $this->eventManager->dispatch(
                $tracking->isCustomer()
                    ? RmaEventNames::TRACKING_NUMBER_ADDED_BY_CUSTOMER
                    : RmaEventNames::TRACKING_NUMBER_ADDED_BY_MANAGER,
                ['tracking' => $tracking, 'request' => $this->getById($tracking->getRequestId())]
            );
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Unable to save new request. Error: %1', $e->getMessage()));
        }

        return $tracking;
    }

    /**
     * @inheritDoc
     */
    public function getTrackingById($trackingId)
    {
        /** @var \Amasty\Rma\Api\Data\TrackingInterface $tracking */
        $tracking = $this->trackingFactory->create();
        $this->trackingResource->load($tracking, $trackingId);
        if (!$tracking->getTrackingId()) {
            throw new NoSuchEntityException(__('Request with specified ID "%1" not found.', $trackingId));
        }

        return $tracking;
    }

    /**
     * @inheritDoc
     */
    public function deleteTrackingById($trackingId)
    {
        $tracking = $this->getTrackingById($trackingId);
        $this->trackingResource->delete($tracking);

        $this->eventManager->dispatch(
            $tracking->isCustomer()
                ? RmaEventNames::TRACKING_NUMBER_DELETED_BY_CUSTOMER
                : RmaEventNames::TRACKING_NUMBER_DELETED_BY_MANAGER,
            ['tracking' => $tracking, 'request' => $this->getById($tracking->getRequestId())]
        );

        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(RequestInterface $request)
    {
        try {
            $this->requestResource->delete($request);

            unset($this->requests[$request->getRequestId()]);
        } catch (\Exception $e) {
            if ($request->getRequestId()) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove request with ID %1. Error: %2',
                        [$request->getRequestId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove request. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($requestId)
    {
        $requestModel = $this->getById($requestId);
        $this->delete($requestModel);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getEmptyRequestModel()
    {
        return $this->requestFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function getEmptyRequestItemModel()
    {
        return $this->requestItemFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function getEmptyTrackingModel()
    {
        return $this->trackingFactory->create();
    }
}
