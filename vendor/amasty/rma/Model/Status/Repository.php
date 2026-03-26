<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Status;

use Amasty\Rma\Api\Data\StatusInterfaceFactory;
use Amasty\Rma\Api\Data\StatusStoreInterfaceFactory;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Api\Data\StatusInterface;
use Amasty\Rma\Api\Data\StatusStoreInterface;
use Amasty\Rma\Model\OptionSource\State;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Repository implements StatusRepositoryInterface
{
    /**
     * @var \Amasty\Rma\Api\Data\StatusInterface[]
     */
    private $statuses;

    /**
     * @var StatusInterfaceFactory
     */
    private $statusFactory;

    /**
     * @var ResourceModel\Status
     */
    private $statusResource;

    /**
     * @var ResourceModel\StatusStoreCollectionFactory
     */
    private $statusStoreCollectionFactory;

    /**
     * @var ResourceModel\StatusStore
     */
    private $statusStoreResource;

    /**
     * @var ResourceModel\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var StatusStoreInterfaceFactory
     */
    private $statusStoreFactory;

    /**
     * @var State
     */
    private $state;

    public function __construct(
        StatusInterfaceFactory $statusFactory,
        StatusStoreInterfaceFactory $statusStoreFactory,
        ResourceModel\Status $statusResource,
        ResourceModel\CollectionFactory $collectionFactory,
        ResourceModel\StatusStoreCollectionFactory $statusStoreCollectionFactory,
        ResourceModel\StatusStore $statusStoreResource,
        State $state
    ) {
        $this->statusFactory = $statusFactory;
        $this->statusResource = $statusResource;
        $this->statusStoreCollectionFactory = $statusStoreCollectionFactory;
        $this->statusStoreResource = $statusStoreResource;
        $this->collectionFactory = $collectionFactory;
        $this->statusStoreFactory = $statusStoreFactory;
        $this->state = $state;
    }

    /**
     * @inheritdoc
     */
    public function getById($statusId, $storeId = null)
    {
        if (!isset($this->statuses[$statusId][$storeId])) {
            /** @var \Amasty\Rma\Api\Data\StatusInterface $status */
            $status = $this->statusFactory->create();
            $this->statusResource->load($status, $statusId);
            if (!$status->getStatusId()) {
                throw new NoSuchEntityException(__('Status with specified ID "%1" not found.', $statusId));
            }
            if ($storeId !== null) {
                /** @var ResourceModel\StatusStoreCollection $statusStoreCollection */
                $statusStoreCollection = $this->statusStoreCollectionFactory->create();
                $statusStoreCollection->addFieldToFilter(
                    StatusInterface::STATUS_ID,
                    $status->getStatusId()
                )->addFieldToFilter(StatusStoreInterface::STORE_ID, [0, (int)$storeId])
                ->addOrder(
                    StatusStoreInterface::STORE_ID,
                    \Magento\Framework\Data\Collection::SORT_ORDER_ASC
                );
                $statusStore = $this->statusStoreFactory->create();
                /** @var \Amasty\Rma\Api\Data\StatusStoreInterface $item */
                foreach ($statusStoreCollection->getData() as $item) {
                    foreach ($item as $key => $value) {
                        if (!empty($item[$key] || empty($statusStore->getData($key)))) {
                            $statusStore->setData($key, $value);
                        }
                    }
                }
                if (empty($statusStore->getLabel())) {
                    $statusStore->setLabel($status->getTitle());
                }
                $status->setStore($statusStore);
            } else {
                /** @var ResourceModel\StatusStoreCollection $statusStoreCollection */
                $statusStoreCollection = $this->statusStoreCollectionFactory->create();
                $statusStoreCollection->addFieldToFilter(
                    StatusInterface::STATUS_ID,
                    $status->getStatusId()
                );
                $statusStores = [];
                foreach ($statusStoreCollection->getItems() as $statusStore) {
                    $statusStores[$statusStore->getStoreId()] = $statusStore;
                }
                $status->setStores($statusStores);
            }

            $this->statuses[$statusId][$storeId] = $status;
        }

        return $this->statuses[$statusId][$storeId];
    }

    /**
     * @inheritdoc
     */
    public function getStatusesByStoreId($storeId, $enabledOnly = true, $withDeleted = false)
    {
        $statusStoreCollection = $this->statusStoreCollectionFactory->create();
        $statusStoreCollection->addFieldToFilter(StatusStoreInterface::STORE_ID, [(int)$storeId, 0])
            ->addOrder(
                StatusStoreInterface::STORE_ID,
                \Magento\Framework\Data\Collection::SORT_ORDER_ASC
            );

        $statuses = [];
        foreach ($statusStoreCollection->getData() as $statusStore) {
            if (!empty($statusStore[StatusStoreInterface::LABEL])
                || empty($statuses[$statusStore[StatusStoreInterface::STATUS_ID]])) {
                $statuses[$statusStore[StatusStoreInterface::STATUS_ID]] =
                    $statusStore[StatusStoreInterface::LABEL];
            }
        }

        $collection = $this->collectionFactory->create();
        if ($enabledOnly) {
            $collection->addFieldToFilter(
                StatusInterface::IS_ENABLED,
                \Amasty\Rma\Model\OptionSource\Status::ENABLED
            );
        }
        if (!$withDeleted) {
            $collection->addNotDeletedFilter();
        }

        $result = [];
        /** @var StatusInterface $status */
        foreach ($collection->getItems() as $status) {
            $result[$status->getStatusId()] = $status->setLabel(
                !empty($statuses[$status->getStatusId()]) ? $statuses[$status->getStatusId()] : $status->getTitle()
            );
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function save(\Amasty\Rma\Api\Data\StatusInterface $status)
    {
        try {
            if (!$status->isInitial()) {
                $initialStatus = $this->collectionFactory->create()
                    ->addFieldToFilter(StatusInterface::IS_INITIAL, 1)
                    ->addFieldToSelect(StatusInterface::STATUS_ID)
                    ->getData();

                if (!$initialStatus || ($status->getStatusId()
                        && $status->getStatusId() == $initialStatus[0][StatusInterface::STATUS_ID])
                ) {
                    throw new LocalizedException(__('There is no initial status.'));
                }
            }

            if ($status->isInitial() && !$status->isEnabled()) {
                throw new LocalizedException(__('Initial status can\'t be disabled.'));
            }

            if ($status->getStatusId()) {
                //TODO check if state changed
                if (!$status->isEnabled() && $this->isLastState($status)) {
                    $states = $this->state->toArray();

                    throw new LocalizedException(
                        __(
                            'Can\'t disable status because it is the only status of state "%1".',
                            $states[$status->getState()]
                        )
                    );
                }
            }
            if ($status->getStatusId()) {
                $status = $this->getById($status->getStatusId())->addData($status->getData());
            }

            if ($status->getState() === State::CANCELED) {
                $status->setIsEnabled(true)->setIsInitial(false);
            }

            $this->statusResource->save($status);
            if ($status->getAutoEvent()) {
                $this->statusResource->unsetAutoEvent(
                    $status->getAutoEvent(),
                    $status->getState(),
                    $status->getStatusId()
                );
            }
            if ($status->isInitial()) {
                $this->statusResource->unsetPreviousInitialStatus($status->getStatusId());
            }

            /** @var ResourceModel\StatusStoreCollection $statusStoreCollection */
            $statusStoreCollection = $this->statusStoreCollectionFactory->create();
            $statusStoreCollection->addFieldToFilter(
                StatusStoreInterface::STATUS_ID,
                $status->getStatusId()
            );
            $statusStoreCollection->walk('delete');
            if ($stores = $status->getStores()) {
                foreach ($stores as $store) {
                    $store->setStatusId($status->getStatusId());
                    $this->statusStoreResource->save($store);
                }
            }

            unset($this->statuses[$status->getStatusId()]);
        } catch (\Exception $e) {
            if ($status->getStatusId()) {
                throw new CouldNotSaveException(
                    __(
                        'Unable to save status with ID %1. Error: %2',
                        [$status->getStatusId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotSaveException(__('Unable to save new status. Error: %1', $e->getMessage()));
        }

        return $status;
    }

    /**
     * @inheritdoc
     */
    public function clearDeleted()
    {
        $statusCollection = $this->collectionFactory->create();
        $statusCollection->addFieldToFilter(StatusInterface::IS_DELETED, 1);

        try {
            foreach ($statusCollection->getItems() as $status) {
                $statusId = $status->getStatusId();
                $this->statusResource->delete($status);
                unset($this->statuses[$statusId]);
            }
        } catch (\Exception $e) {
            if ($statusId) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove status with ID %1. Error: %2',
                        [$statusId, $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove status. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function delete(StatusInterface $status)
    {
        if ($status->isInitial()) {
            throw new LocalizedException(__('Can\'t delete initial status.'));
        }
        if ($this->isLastState($status)) {
            $states = $this->state->toArray();

            throw new LocalizedException(
                __('Can\'t delete status because it is the only status of state "%1".', $states[$status->getState()])
            );
        }
        $status->setIsDeleted(true);
        $this->save($status);
        unset($this->statuses[$status->getStatusId()]);

        return true;
    }

    /**
     * @param StatusInterface $status
     *
     * @return bool
     */
    private function isLastState(StatusInterface $status)
    {
        $collection = $this->getEmptyStatusCollection();
        $collection->addFieldToFilter(StatusInterface::STATE, $status->getState())->addNotDeletedFilter();

        return $collection->count() <= 1;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($statusId)
    {
        $status = $this->getById($statusId);

        return $this->delete($status);
    }

    /**
     * @inheritdoc
     */
    public function getInitialStatusId()
    {
        return (int)$this->collectionFactory->create()->addFieldToFilter(StatusInterface::IS_INITIAL, 1)
            ->fetchItem()
            ->getStatusId();
    }

    /**
     * @inheritdoc
     */
    public function getCancelStatusId()
    {
        return (int)$this->collectionFactory->create()
            ->addFieldToFilter(StatusInterface::STATE, State::CANCELED)
            ->fetchItem()
            ->getStatusId();
    }

    /**
     * @return \Amasty\Rma\Api\Data\StatusInterface
     */
    public function getEmptyStatusModel()
    {
        return $this->statusFactory->create();
    }

    /**
     * @return \Amasty\Rma\Api\Data\StatusStoreInterface
     */
    public function getEmptyStatusStoreModel()
    {
        return $this->statusStoreFactory->create();
    }

    public function getEmptyStatusCollection()
    {
        return $this->collectionFactory->create();
    }
}
