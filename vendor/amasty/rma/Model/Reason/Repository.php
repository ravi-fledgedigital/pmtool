<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Reason;

use Amasty\Rma\Api\Data\ReasonInterfaceFactory;
use Amasty\Rma\Api\Data\ReasonStoreInterfaceFactory;
use Amasty\Rma\Api\ReasonRepositoryInterface;
use Amasty\Rma\Api\Data\ReasonInterface;
use Amasty\Rma\Api\Data\ReasonStoreInterface;
use Amasty\Rma\Model\OptionSource\Status;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class Repository implements ReasonRepositoryInterface
{
    /**
     * @var \Amasty\Rma\Api\Data\ReasonInterface[]
     */
    private $reasons;

    /**
     * @var ReasonInterfaceFactory
     */
    private $reasonFactory;

    /**
     * @var ResourceModel\Reason
     */
    private $reasonResource;

    /**
     * @var ResourceModel\ReasonStoreCollectionFactory
     */
    private $reasonStoreCollectionFactory;

    /**
     * @var ResourceModel\ReasonStore
     */
    private $reasonStoreResource;

    /**
     * @var ResourceModel\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ReasonStoreInterfaceFactory
     */
    private $reasonStoreFactory;

    /**
     * @var ReasonInterface[]
     */
    private $storeReasons;

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
     * @inheritdoc
     */
    public function getById($reasonId, $storeId = null)
    {
        if (!isset($this->reasons[$reasonId][$storeId])) {
            /** @var \Amasty\Rma\Api\Data\ReasonInterface $reason */
            $reason = $this->reasonFactory->create();
            $this->reasonResource->load($reason, $reasonId);
            if (!$reason->getReasonId()) {
                throw new NoSuchEntityException(__('Reason with specified ID "%1" not found.', $reasonId));
            }
            if ($storeId !== null) {
                /** @var ResourceModel\ReasonStoreCollection $reasonStoreCollection */
                $reasonStoreCollection = $this->reasonStoreCollectionFactory->create();
                $reasonStoreCollection->addFieldToFilter(
                    ReasonInterface::REASON_ID,
                    $reason->getReasonId()
                )->addFieldToFilter(ReasonStoreInterface::STORE_ID, [0, (int)$storeId])
                ->addOrder(
                    ReasonStoreInterface::STORE_ID,
                    \Magento\Framework\Data\Collection::SORT_ORDER_ASC
                );
                $reasonStore = $this->reasonStoreFactory->create();
                /** @var \Amasty\Rma\Api\Data\ReasonStoreInterface $item */
                foreach ($reasonStoreCollection->getData() as $item) {
                    foreach ($item as $key => $value) {
                        if (!empty($item[$key]) || empty($reasonStore->getData($key))) {
                            $reasonStore->setData($key, $value);
                        }
                    }
                }
                if (empty($reasonStore->getLabel())) {
                    $reasonStore->setLabel($reason->getTitle());
                }
                $reason->setStore($reasonStore);
                $reason->setStores([$reasonStore]); // For webapi representation
            } else {
                /** @var ResourceModel\ReasonStoreCollection $reasonStoreCollection */
                $reasonStoreCollection = $this->reasonStoreCollectionFactory->create();
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
     * @inheritdoc
     */
    public function getReasonsByStoreId($storeId, $enabledOnly = true, $withDeleted = false)
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
        $collection->addOrder(ReasonInterface::POSITION, \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
        if ($enabledOnly) {
            $collection->addFieldToFilter(ReasonInterface::STATUS, Status::ENABLED);
        }
        if (!$withDeleted) {
            $collection->addNotDeletedFilter();
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
     * @inheritdoc
     */
    public function save(\Amasty\Rma\Api\Data\ReasonInterface $reason)
    {
        try {
            if ($reason->getReasonId()) {
                $reason = $this->getById($reason->getReasonId())->addData($reason->getData());
            }

            $this->reasonResource->save($reason);

            /** @var ResourceModel\ReasonStoreCollection $reasonStoreCollection */
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
     * @inheritdoc
     */
    public function clearDeleted()
    {
        $reasonCollection = $this->collectionFactory->create();
        $reasonCollection->addFieldToFilter(ReasonInterface::IS_DELETED, 1);

        try {
            foreach ($reasonCollection->getItems() as $reason) {
                $reasonId = $reason->getReasonId();
                $this->reasonResource->delete($reason);
                unset($this->reasons[$reasonId]);
            }
        } catch (\Exception $e) {
            if ($reasonId) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove reason with ID %1. Error: %2',
                        [$reasonId, $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove reason. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function delete(ReasonInterface $reason)
    {
        $reason->setIsDeleted(true);
        $this->save($reason);
        unset($this->reasons[$reason->getReasonId()]);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($reasonId)
    {
        $reason = $this->getById($reasonId);

        return $this->delete($reason);
    }

    /**
     * @return \Amasty\Rma\Api\Data\ReasonInterface
     */
    public function getEmptyReasonModel()
    {
        return $this->reasonFactory->create();
    }

    /**
     * @return \Amasty\Rma\Api\Data\ReasonStoreInterface
     */
    public function getEmptyReasonStoreModel()
    {
        return $this->reasonStoreFactory->create();
    }
}
