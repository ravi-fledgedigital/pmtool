<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Resolution;

use Amasty\Rma\Api\Data\ResolutionInterfaceFactory;
use Amasty\Rma\Api\Data\ResolutionStoreInterfaceFactory;
use Amasty\Rma\Api\ResolutionRepositoryInterface;
use Amasty\Rma\Api\Data\ResolutionInterface;
use Amasty\Rma\Api\Data\ResolutionStoreInterface;
use Amasty\Rma\Model\OptionSource\Status;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class Repository implements ResolutionRepositoryInterface
{
    /**
     * @var \Amasty\Rma\Api\Data\ResolutionInterface[]
     */
    private $resolutions;

    /**
     * @var ResolutionInterfaceFactory
     */
    private $resolutionFactory;

    /**
     * @var ResourceModel\Resolution
     */
    private $resolutionResource;

    /**
     * @var ResourceModel\ResolutionStoreCollectionFactory
     */
    private $resolutionStoreCollectionFactory;

    /**
     * @var ResourceModel\ResolutionStore
     */
    private $resolutionStoreResource;

    /**
     * @var ResourceModel\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ResolutionStoreInterfaceFactory
     */
    private $resolutionStoreFactory;

    /**
     * @var ResolutionInterface[]
     */
    private $storeResolutions;

    public function __construct(
        ResolutionInterfaceFactory $resolutionFactory,
        ResolutionStoreInterfaceFactory $resolutionStoreFactory,
        ResourceModel\Resolution $resolutionResource,
        ResourceModel\CollectionFactory $collectionFactory,
        ResourceModel\ResolutionStoreCollectionFactory $resolutionStoreCollectionFactory,
        ResourceModel\ResolutionStore $resolutionStoreResource
    ) {
        $this->resolutionFactory = $resolutionFactory;
        $this->resolutionResource = $resolutionResource;
        $this->resolutionStoreCollectionFactory = $resolutionStoreCollectionFactory;
        $this->resolutionStoreResource = $resolutionStoreResource;
        $this->collectionFactory = $collectionFactory;
        $this->resolutionStoreFactory = $resolutionStoreFactory;
    }

    /**
     * @inheritdoc
     */
    public function getById($resolutionId, $storeId = null)
    {
        if (!isset($this->resolutions[$resolutionId][$storeId])) {
            /** @var \Amasty\Rma\Api\Data\ResolutionInterface $resolution */
            $resolution = $this->resolutionFactory->create();
            $this->resolutionResource->load($resolution, $resolutionId);
            if (!$resolution->getResolutionId()) {
                throw new NoSuchEntityException(__('Resolution with specified ID "%1" not found.', $resolutionId));
            }
            if ($storeId !== null) {
                /** @var ResourceModel\ResolutionStoreCollection $resolutionStoreCollection */
                $resolutionStoreCollection = $this->resolutionStoreCollectionFactory->create();
                $resolutionStoreCollection->addFieldToFilter(
                    ResolutionInterface::RESOLUTION_ID,
                    $resolution->getResolutionId()
                )->addFieldToFilter(ResolutionStoreInterface::STORE_ID, [0, (int)$storeId])
                ->addOrder(
                    ResolutionStoreInterface::STORE_ID,
                    \Magento\Framework\Data\Collection::SORT_ORDER_ASC
                );
                $resolutionStore = $this->resolutionStoreFactory->create();
                /** @var \Amasty\Rma\Api\Data\ResolutionStoreInterface $item */
                foreach ($resolutionStoreCollection->getData() as $item) {
                    foreach ($item as $key => $value) {
                        if (!empty($item[$key]) || empty($resolutionStore->getData($key))) {
                            $resolutionStore->setData($key, $value);
                        }
                    }
                }
                if (empty($resolutionStore->getLabel())) {
                    $resolutionStore->setLabel($resolution->getTitle());
                }
                $resolution->setStore($resolutionStore);
                $resolution->setStores([$resolutionStore]); // For webapi representation
            } else {
                /** @var ResourceModel\ResolutionStoreCollection $resolutionStoreCollection */
                $resolutionStoreCollection = $this->resolutionStoreCollectionFactory->create();
                $resolutionStoreCollection->addFieldToFilter(
                    ResolutionInterface::RESOLUTION_ID,
                    $resolution->getResolutionId()
                );
                $resolutionStores = [];
                foreach ($resolutionStoreCollection->getItems() as $resolutionStore) {
                    $resolutionStores[$resolutionStore->getStoreId()] = $resolutionStore;
                }
                $resolution->setStores($resolutionStores);
            }
            $this->resolutions[$resolutionId][$storeId] = $resolution;
        }

        return $this->resolutions[$resolutionId][$storeId];
    }

    /**
     * @inheritdoc
     */
    public function getResolutionsByStoreId($storeId, $enabledOnly = true, $withDeleted = false)
    {
        if (isset($this->storeResolutions[$storeId][$enabledOnly])) {
            return $this->storeResolutions[$storeId][$enabledOnly];
        }

        $resolutionStoreCollection = $this->resolutionStoreCollectionFactory->create();
        $resolutionStoreCollection->addFieldToFilter(ResolutionStoreInterface::STORE_ID, [(int)$storeId, 0])
            ->addOrder(
                ResolutionStoreInterface::STORE_ID,
                \Magento\Framework\Data\Collection::SORT_ORDER_ASC
            );

        $resolutions = [];
        foreach ($resolutionStoreCollection->getData() as $resolutionStore) {
            if (!empty($resolutionStore[ResolutionStoreInterface::LABEL])
                || empty($resolutions[$resolutionStore[ResolutionStoreInterface::RESOLUTION_ID]])) {
                $resolutions[$resolutionStore[ResolutionStoreInterface::RESOLUTION_ID]] =
                    $resolutionStore[ResolutionStoreInterface::LABEL];
            }
        }

        $collection = $this->collectionFactory->create();
        $collection->addOrder(ResolutionInterface::POSITION);
        if ($enabledOnly) {
            $collection->addFieldToFilter(ResolutionInterface::STATUS, Status::ENABLED);
        }
        if (!$withDeleted) {
            $collection->addNotDeletedFilter();
        }

        $result = [];
        /** @var ResolutionInterface $resolution */
        foreach ($collection->getItems() as $resolution) {
            $result[$resolution->getResolutionId()] = $resolution->setLabel(
                !empty($resolutions[$resolution->getResolutionId()])
                        ? $resolutions[$resolution->getResolutionId()]
                        : $resolution->getTitle()
            );
        }

        $this->storeResolutions[$storeId][$enabledOnly] = $result;

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function save(\Amasty\Rma\Api\Data\ResolutionInterface $resolution)
    {
        try {
            if ($resolution->getResolutionId()) {
                $resolution = $this->getById($resolution->getResolutionId())->addData($resolution->getData());
            }

            $this->resolutionResource->save($resolution);

            /** @var ResourceModel\ResolutionStoreCollection $resolutionStoreCollection */
            $resolutionStoreCollection = $this->resolutionStoreCollectionFactory->create();
            $resolutionStoreCollection->addFieldToFilter(
                ResolutionStoreInterface::RESOLUTION_ID,
                $resolution->getResolutionId()
            );
            $resolutionStoreCollection->walk('delete');
            if ($stores = $resolution->getStores()) {
                foreach ($stores as $store) {
                    $store->setResolutionId($resolution->getResolutionId());
                    $this->resolutionStoreResource->save($store);
                }
            }

            unset($this->resolutions[$resolution->getResolutionId()]);
        } catch (\Exception $e) {
            if ($resolution->getResolutionId()) {
                throw new CouldNotSaveException(
                    __(
                        'Unable to save resolution with ID %1. Error: %2',
                        [$resolution->getResolutionId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotSaveException(__('Unable to save new resolution. Error: %1', $e->getMessage()));
        }

        return $resolution;
    }

    /**
     * @inheritdoc
     */
    public function clearDeleted()
    {
        $resolutionCollection = $this->collectionFactory->create();
        $resolutionCollection->addFieldToFilter(ResolutionInterface::IS_DELETED, 1);

        try {
            foreach ($resolutionCollection->getItems() as $resolution) {
                $resolutionId = $resolution->getResolutionId();
                $this->resolutionResource->delete($resolution);
                unset($this->resolutions[$resolutionId]);
            }
        } catch (\Exception $e) {
            if ($resolutionId) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove resolution with ID %1. Error: %2',
                        [$resolutionId, $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove resolution. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function delete(ResolutionInterface $resolution)
    {
        $resolution->setIsDeleted(true);
        $this->save($resolution);
        unset($this->resolutions[$resolution->getResolutionId()]);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($resolutionId)
    {
        $resolution = $this->getById($resolutionId);

        return $this->delete($resolution);
    }

    /**
     * @return \Amasty\Rma\Api\Data\ResolutionInterface
     */
    public function getEmptyResolutionModel()
    {
        return $this->resolutionFactory->create();
    }

    /**
     * @return \Amasty\Rma\Api\Data\ResolutionStoreInterface
     */
    public function getEmptyResolutionStoreModel()
    {
        return $this->resolutionStoreFactory->create();
    }
}
