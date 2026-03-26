<?php

namespace OnitsukaTigerKorea\OrderCancel\Model\Reason;

use OnitsukaTigerKorea\OrderCancel\Model\OptionSource\Status;
use Magento\Framework\Exception\CouldNotDeleteException;
use OnitsukaTigerKorea\OrderCancel\Api\Data\ReasonInterfaceFactory;
use OnitsukaTigerKorea\OrderCancel\Api\Data\ReasonStoreInterfaceFactory;
use OnitsukaTigerKorea\OrderCancel\Api\ReasonRepositoryInterface;
use OnitsukaTigerKorea\OrderCancel\Api\Data\ReasonInterface;
use OnitsukaTigerKorea\OrderCancel\Api\Data\ReasonStoreInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class Repository implements ReasonRepositoryInterface
{
    /**
     * @var ReasonInterface[]
     */
    private array $reasons;

    /**
     * @var ReasonInterfaceFactory
     */
    private ReasonInterfaceFactory $reasonFactory;

    /**
     * @var ResourceModel\Reason
     */
    private ResourceModel\Reason $reasonResource;

    /**
     * @var ResourceModel\ReasonStoreCollectionFactory
     */
    private ResourceModel\ReasonStoreCollectionFactory $reasonStoreCollectionFactory;

    /**
     * @var ResourceModel\ReasonStore
     */
    private ResourceModel\ReasonStore $reasonStoreResource;

    /**
     * @var ResourceModel\CollectionFactory
     */
    private ResourceModel\CollectionFactory $collectionFactory;

    /**
     * @var ReasonStoreInterfaceFactory
     */
    private ReasonStoreInterfaceFactory $reasonStoreFactory;

    /**
     * @var ReasonInterface[]
     */
    private array $storeReasons;

    /**
     * @param ReasonInterfaceFactory $reasonFactory
     * @param ReasonStoreInterfaceFactory $reasonStoreFactory
     * @param ResourceModel\Reason $reasonResource
     * @param ResourceModel\CollectionFactory $collectionFactory
     * @param ResourceModel\ReasonStoreCollectionFactory $reasonStoreCollectionFactory
     * @param ResourceModel\ReasonStore $reasonStoreResource
     */
    public function __construct(
        ReasonInterfaceFactory $reasonFactory,
        ReasonStoreInterfaceFactory $reasonStoreFactory,
        ResourceModel\Reason $reasonResource,
        ResourceModel\CollectionFactory $collectionFactory,
        ResourceModel\ReasonStoreCollectionFactory $reasonStoreCollectionFactory,
        ResourceModel\ReasonStore $reasonStoreResource
    ) {
        $this->reasonFactory = $reasonFactory;
        $this->reasonResource = $reasonResource;
        $this->reasonStoreCollectionFactory = $reasonStoreCollectionFactory;
        $this->reasonStoreResource = $reasonStoreResource;
        $this->collectionFactory = $collectionFactory;
        $this->reasonStoreFactory = $reasonStoreFactory;
    }

    /**
     * @param int $reasonId
     * @param int|null $storeId
     *
     * @return ReasonInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $reasonId, int $storeId = null): ReasonInterface
    {
        if (!isset($this->reasons[$reasonId][$storeId])) {
            $reason = $this->reasonFactory->create();
            $this->reasonResource->load($reason, $reasonId);
            if (!$reason->getReasonId()) {
                throw new NoSuchEntityException(__('Reason with specified ID "%1" not found.', $reasonId));
            }
            $reasonStoreCollection = $this->reasonStoreCollectionFactory->create();
            if ($storeId !== null) {
                $reasonStoreCollection->addFieldToFilter(
                    ReasonInterface::REASON_ID,
                    $reason->getReasonId()
                )->addFieldToFilter(ReasonStoreInterface::STORE_ID, [0, (int)$storeId])
                ->addOrder(
                    ReasonStoreInterface::STORE_ID,
                    \Magento\Framework\Data\Collection::SORT_ORDER_ASC
                );
                $reasonStore = $this->reasonStoreFactory->create();
                /** @var ReasonStoreInterface $item */
                foreach ($reasonStoreCollection->getData() as $item) {
                    foreach ($item as $key => $value) {
                        if (!empty($value) || empty($reasonStore->getData($key))) {
                            $reasonStore->setData($key, $value);
                        }
                    }
                }
                if (empty($reasonStore->getLabel())) {
                    $reasonStore->setLabel($reason->getTitle());
                }
                $reason->setStore($reasonStore);
                $reason->setStores([$reasonStore]);
            } else {
                $reasonStoreCollection->addFieldToFilter(
                    ReasonInterface::REASON_ID,
                    $reason->getReasonId()
                );
                $reasonStores = [];
                foreach ($reasonStoreCollection->getItems() as $reasonStore) {
                    $reasonStores[$reasonStore->getStoreId()] = $reasonStore;
                }
                $reason->setStores($reasonStores);
            }
            $this->reasons[$reasonId][$storeId] = $reason;
        }

        return $this->reasons[$reasonId][$storeId];
    }

    /**
     * @param int $storeId
     * @param bool $enabledOnly
     *
     * @return ReasonInterface[]
     */
    public function getReasonsByStoreId(int $storeId, bool $enabledOnly = true): array
    {
        if (isset($this->storeReasons[$storeId][$enabledOnly])) {
            return $this->storeReasons[$storeId][$enabledOnly];
        }

        $reasonStoreCollection = $this->reasonStoreCollectionFactory->create();
        $reasonStoreCollection->addFieldToFilter(ReasonStoreInterface::STORE_ID, [(int)$storeId, 0])
            ->addOrder(
                ReasonStoreInterface::STORE_ID,
                \Magento\Framework\Data\Collection::SORT_ORDER_ASC
            );

        $reasons = [];
        foreach ($reasonStoreCollection->getData() as $reasonStore) {
            if (!empty($reasonStore[ReasonStoreInterface::LABEL])
                || empty($reasons[$reasonStore[ReasonStoreInterface::REASON_ID]])) {
                $reasons[$reasonStore[ReasonStoreInterface::REASON_ID]] =
                    $reasonStore[ReasonStoreInterface::LABEL];
            }
        }

        $collection = $this->collectionFactory->create();
        if ($enabledOnly) {
            $collection->addFieldToFilter(ReasonInterface::STATUS, Status::ENABLED);
        }

        $result = [];
        /** @var ReasonInterface $reason */
        foreach ($collection->getItems() as $reason) {
            $result[$reason->getReasonId()] = $reason->setLabel(
                !empty($reasons[$reason->getReasonId()])
                    ? $reasons[$reason->getReasonId()]
                    : $reason->getTitle()
            );
        }

        $this->storeReasons[$storeId][$enabledOnly] = $result;

        return $result;
    }

    /**
     * @param ReasonInterface $reason
     *
     * @return ReasonInterface
     * @throws CouldNotSaveException
     */
    public function save(ReasonInterface $reason): ReasonInterface
    {
        try {
            if ($reason->getReasonId()) {
                $reason = $this->getById($reason->getReasonId())->addData($reason->getData());
            }

            $this->reasonResource->save($reason);

            $reasonStoreCollection = $this->reasonStoreCollectionFactory->create();
            $reasonStoreCollection->addFieldToFilter(ReasonStoreInterface::REASON_ID, $reason->getReasonId());
            $reasonStoreCollection->walk('delete');
            if ($stores = $reason->getStores()) {
                foreach ($stores as $store) {
                    $store->setReasonId($reason->getReasonId());
                    $this->reasonStoreResource->save($store);
                }
            }

            unset($this->reasons[$reason->getReasonId()]);
        } catch (\Exception $e) {
            if ($reason->getReasonId()) {
                throw new CouldNotSaveException(
                    __(
                        'Unable to save reason with ID %1. Error: %2',
                        [$reason->getReasonId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotSaveException(__('Unable to save new reason. Error: %1', $e->getMessage()));
        }

        return $reason;
    }


    /**
     * @param ReasonInterface $reason
     *
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function delete(ReasonInterface $reason): bool
    {
        try {
            $this->reasonResource->delete($reason);
        } catch (\Exception $e) {
            if ($reason->getReasonId()) {
                throw new CouldNotDeleteException(
                    __('Unable to remove reason with ID %1. Error: %2', [$reason->getReasonId(), $e->getMessage()])
                );
            }

            throw new CouldNotDeleteException(__('Unable to remove reason. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @param int $reasonId
     *
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $reasonId): bool
    {
        $reason = $this->getById($reasonId);

        $this->delete($reason);
        return true;
    }

    /**
     * @return ReasonInterface
     */
    public function getEmptyReasonModel(): ReasonInterface
    {
        return $this->reasonFactory->create();
    }

    /**
     * @return ReasonStoreInterface
     */
    public function getEmptyReasonStoreModel(): ReasonStoreInterface
    {
        return $this->reasonStoreFactory->create();
    }
}
