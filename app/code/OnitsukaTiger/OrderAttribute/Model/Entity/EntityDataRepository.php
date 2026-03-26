<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Entity;

use OnitsukaTiger\OrderAttribute\Api\Data\EntityDataInterface;
use OnitsukaTiger\OrderAttribute\Api\EntityDataRepositoryInterface;
use OnitsukaTiger\OrderAttribute\Model\Entity\EntityDataFactory;
use OnitsukaTiger\OrderAttribute\Model\Entity\Handler\Save as SaveHandler;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity as EntityResource;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\EntityData\CollectionFactory;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\EntityData\Collection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Ui\Api\Data\BookmarkSearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EntityDataRepository implements EntityDataRepositoryInterface
{
    /**
     * @var BookmarkSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var EntityDataFactory
     */
    private $entityDataFactory;

    /**
     * @var EntityResource
     */
    private $entityResource;

    /**
     * Model data storage
     *
     * @var array
     */
    private $entityDatas;

    /**
     * @var CollectionFactory
     */
    private $entityDataCollectionFactory;

    /**
     * @var SaveHandler
     */
    private $saveHandler;

    public function __construct(
        BookmarkSearchResultsInterfaceFactory $searchResultsFactory,
        EntityDataFactory $entityDataFactory,
        EntityResource $entityResource,
        CollectionFactory $entityDataCollectionFactory,
        SaveHandler $saveHandler
    ) {
        $this->searchResultsFactory = $searchResultsFactory;
        $this->entityDataFactory = $entityDataFactory;
        $this->entityResource = $entityResource;
        $this->entityDataCollectionFactory = $entityDataCollectionFactory;
        $this->saveHandler = $saveHandler;
    }

    /**
     * @inheritdoc
     */
    public function save(EntityDataInterface $entityData)
    {
        return $this->saveHandler->execute($entityData);
    }

    /**
     * @inheritdoc
     */
    public function getById($entityId)
    {
        if (!isset($this->entityDatas[$entityId])) {
            /** @var \OnitsukaTiger\OrderAttribute\Model\Entity\EntityData $entityData */
            $entityData = $this->entityDataFactory->create();
            $this->entityResource->load($entityData, $entityId);
            if (!$entityData->getEntityId()) {
                throw new NoSuchEntityException(__('EntityData with specified ID "%1" not found.', $entityId));
            }
            $this->entityDatas[$entityId] = $entityData;
        }

        return $this->entityDatas[$entityId];
    }

    /**
     * @inheritdoc
     */
    public function delete(EntityDataInterface $entityData)
    {
        try {
            $this->entityResource->delete($entityData);
            unset($this->entityDatas[$entityData->getEntityId()]);
        } catch (\Exception $e) {
            if ($entityData->getEntityId()) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove entityData with ID %1. Error: %2',
                        [$entityData->getEntityId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove entityData. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($entityId)
    {
        $entityDataModel = $this->getById($entityId);
        $this->delete($entityDataModel);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        /** @var \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\EntityData\Collection $entityDataCollection */
        $entityDataCollection = $this->entityDataCollectionFactory->create();
        // Add filters from root filter group to the collection
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $entityDataCollection);
        }
        $searchResults->setTotalCount($entityDataCollection->getSize());
        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            $this->addOrderToCollection($sortOrders, $entityDataCollection);
        }
        $entityDataCollection->setCurPage($searchCriteria->getCurrentPage());
        $entityDataCollection->setPageSize($searchCriteria->getPageSize());
        $entityDatas = [];
        /** @var EntityDataInterface $entityData */
        foreach ($entityDataCollection->getItems() as $entityData) {
            $entityDatas[] = $this->getById($entityData->getId());
        }
        $searchResults->setItems($entityDatas);

        return $searchResults;
    }

    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param FilterGroup $filterGroup
     * @param Collection  $entityDataCollection
     *
     * @return void
     */
    private function addFilterGroupToCollection(FilterGroup $filterGroup, Collection $entityDataCollection)
    {
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
            $entityDataCollection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
        }
    }

    /**
    * Helper function that adds a SortOrder to the collection.
    *
    * @param SortOrder[] $sortOrders
    * @param Collection  $entityDataCollection
    *
    * @return void
    */
    private function addOrderToCollection($sortOrders, Collection $entityDataCollection)
    {
        /** @var SortOrder $sortOrder */
        foreach ($sortOrders as $sortOrder) {
            $field = $sortOrder->getField();
            $entityDataCollection->addOrder(
                $field,
                ($sortOrder->getDirection() == SortOrder::SORT_DESC) ? 'DESC' : 'ASC'
            );
        }
    }
}
