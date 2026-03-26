<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Condition;

use Amasty\Rma\Api\ConditionRepositoryInterface;
use Amasty\Rma\Api\Data\ConditionInterface;
use Amasty\Rma\Api\Data\ConditionInterfaceFactory;
use Amasty\Rma\Api\Data\ConditionStoreInterfaceFactory;
use Amasty\Rma\Api\Data\ConditionStoreInterface;
use Amasty\Rma\Model\OptionSource\Status;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class Repository implements ConditionRepositoryInterface
{
    /**
     * @var \Amasty\Rma\Api\Data\ConditionInterface[]
     */
    private $conditions;

    /**
     * @var ConditionInterfaceFactory
     */
    private $conditionFactory;

    /**
     * @var ResourceModel\Condition
     */
    private $conditionResource;

    /**
     * @var ResourceModel\ConditionStoreCollectionFactory
     */
    private $conditionStoreCollectionFactory;

    /**
     * @var ResourceModel\ConditionStore
     */
    private $conditionStoreResource;

    /**
     * @var ResourceModel\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ConditionStoreInterfaceFactory
     */
    private $conditionStoreFactory;

    /**
     * @var ConditionInterface[]
     */
    private $storeConditions;

    public function __construct(
        ConditionInterfaceFactory $conditionFactory,
        ResourceModel\Condition $conditionResource,
        ResourceModel\CollectionFactory $collectionFactory,
        ResourceModel\ConditionStoreCollectionFactory $conditionStoreCollectionFactory,
        ResourceModel\ConditionStore $conditionStoreResource,
        ConditionStoreInterfaceFactory $conditionStoreFactory
    ) {
        $this->conditionFactory = $conditionFactory;
        $this->conditionResource = $conditionResource;
        $this->conditionStoreCollectionFactory = $conditionStoreCollectionFactory;
        $this->conditionStoreResource = $conditionStoreResource;
        $this->collectionFactory = $collectionFactory;
        $this->conditionStoreFactory = $conditionStoreFactory;
    }

    /**
     * @inheritdoc
     */
    public function getById($conditionId, $storeId = null)
    {
        if (!isset($this->conditions[$conditionId][$storeId])) {
            /** @var \Amasty\Rma\Api\Data\ConditionInterface $condition */
            $condition = $this->conditionFactory->create();
            $this->conditionResource->load($condition, $conditionId);
            if (!$condition->getConditionId()) {
                throw new NoSuchEntityException(__('Condition with specified ID "%1" not found.', $conditionId));
            }
            if ($storeId !== null) {
                /** @var ResourceModel\ConditionStoreCollection $conditionStoreCollection */
                $conditionStoreCollection = $this->conditionStoreCollectionFactory->create();
                $conditionStoreCollection->addFieldToFilter(
                    ConditionStoreInterface::CONDITION_ID,
                    $condition->getConditionId()
                )->addFieldToFilter(ConditionStoreInterface::STORE_ID, [0, (int)$storeId])
                ->addOrder(
                    ConditionStoreInterface::STORE_ID,
                    \Magento\Framework\Data\Collection::SORT_ORDER_ASC
                );
                $conditionStore = $this->conditionStoreFactory->create();
                /** @var \Amasty\Rma\Api\Data\ConditionStoreInterface $item */
                foreach ($conditionStoreCollection->getData() as $item) {
                    foreach ($item as $key => $value) {
                        if (!empty($item[$key]) || empty($conditionStore->getData($key))) {
                            $conditionStore->setData($key, $value);
                        }
                    }
                }
                if (empty($conditionStore->getLabel())) {
                    $conditionStore->setLabel($condition->getTitle());
                }
                $condition->setStore($conditionStore);
                $condition->setStores([$conditionStore]); // For webapi representation
            } else {
                /** @var ResourceModel\ConditionStoreCollection $conditionStoreCollection */
                $conditionStoreCollection = $this->conditionStoreCollectionFactory->create();
                $conditionStoreCollection->addFieldToFilter(
                    ConditionStoreInterface::CONDITION_ID,
                    $condition->getConditionId()
                );
                $conditionStores = [];
                foreach ($conditionStoreCollection->getItems() as $conditionStore) {
                    $conditionStores[$conditionStore->getStoreId()] = $conditionStore;
                }
                $condition->setStores($conditionStores);
            }
            $this->conditions[$conditionId][$storeId] = $condition;
        }

        return $this->conditions[$conditionId][$storeId];
    }

    /**
     * @inheritdoc
     */
    public function getConditionsByStoreId($storeId, $enabledOnly = true, $withDeleted = false)
    {
        if (isset($this->storeConditions[$storeId][$enabledOnly])) {
            return $this->storeConditions[$storeId][$enabledOnly];
        }

        $conditionStoreCollection = $this->conditionStoreCollectionFactory->create();
        $conditionStoreCollection->addFieldToFilter(ConditionStoreInterface::STORE_ID, [(int)$storeId, 0])
            ->addOrder(
                ConditionStoreInterface::STORE_ID,
                \Magento\Framework\Data\Collection::SORT_ORDER_ASC
            );

        $conditions = [];
        foreach ($conditionStoreCollection->getData() as $conditionStore) {
            if (!empty($conditionStore[ConditionStoreInterface::LABEL])
                || empty($conditions[$conditionStore[ConditionStoreInterface::CONDITION_ID]])) {
                $conditions[$conditionStore[ConditionStoreInterface::CONDITION_ID]] =
                    $conditionStore[ConditionStoreInterface::LABEL];
            }
        }

        $collection = $this->collectionFactory->create();
        $collection->addOrder(ConditionInterface::POSITION);
        if ($enabledOnly) {
            $collection->addFieldToFilter(ConditionInterface::STATUS, Status::ENABLED);
        }
        if (!$withDeleted) {
            $collection->addNotDeletedFilter();
        }

        $result = [];
        /** @var ConditionInterface $condition */
        foreach ($collection->getItems() as $condition) {
            $result[$condition->getConditionId()] = $condition->setLabel(
                !empty($conditions[$condition->getConditionId()])
                        ? $conditions[$condition->getConditionId()]
                        : $condition->getTitle()
            );
        }

        $this->storeConditions[$storeId][$enabledOnly] = $result;

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function save(\Amasty\Rma\Api\Data\ConditionInterface $condition)
    {
        try {
            if ($condition->getConditionId()) {
                $condition = $this->getById($condition->getConditionId())->addData($condition->getData());
            }

            $this->conditionResource->save($condition);

            /** @var ResourceModel\ConditionStoreCollection $conditionCollection */
            $conditionCollection = $this->conditionStoreCollectionFactory->create();
            $conditionCollection->addFieldToFilter(ConditionStoreInterface::CONDITION_ID, $condition->getConditionId());
            $conditionCollection->walk('delete');
            if ($stores = $condition->getStores()) {
                foreach ($stores as $store) {
                    $store->setConditionId($condition->getConditionId());
                    $this->conditionStoreResource->save($store);
                }
            }

            unset($this->conditions[$condition->getConditionId()]);
        } catch (\Exception $e) {
            if ($condition->getConditionId()) {
                throw new CouldNotSaveException(
                    __(
                        'Unable to save condition with ID %1. Error: %2',
                        [$condition->getConditionId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotSaveException(__('Unable to save new condition. Error: %1', $e->getMessage()));
        }

        return $condition;
    }

    /**
     * @inheritdoc
     */
    public function clearDeleted()
    {
        $conditionCollection = $this->collectionFactory->create();
        $conditionCollection->addFieldToFilter(ConditionInterface::IS_DELETED, 1);

        try {
            foreach ($conditionCollection->getItems() as $condition) {
                $conditionId = $condition->getConditionId();
                $this->conditionResource->delete($condition);
                unset($this->conditions[$conditionId]);
            }
        } catch (\Exception $e) {
            if ($conditionId) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove condition with ID %1. Error: %2',
                        [$conditionId, $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove rule. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function delete(ConditionInterface $condition)
    {
        $condition->setIsDeleted(true);
        $this->save($condition);
        unset($this->conditions[$condition->getConditionId()]);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($conditionId)
    {
        $condition = $this->getById($conditionId);

        return $this->delete($condition);
    }

    /**
     * @return \Amasty\Rma\Api\Data\ConditionInterface
     */
    public function getEmptyConditionModel()
    {
        return $this->conditionFactory->create();
    }

    /**
     * @return \Amasty\Rma\Api\Data\ConditionStoreInterface
     */
    public function getEmptyConditionStoreModel()
    {
        return $this->conditionStoreFactory->create();
    }
}
