<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\History;

use Amasty\Rma\Api\Data\HistoryInterface;
use Amasty\Rma\Api\HistoryRepositoryInterface;
use Amasty\Rma\Model\History\ResourceModel\Collection;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\Api\Data\BookmarkSearchResultsInterfaceFactory;

class Repository implements HistoryRepositoryInterface
{
    /**
     * @var ResourceModel\History
     */
    private $historyResource;

    /**
     * @var ResourceModel\CollectionFactory
     */
    private $historyCollectionFactory;

    /**
     * @var HistoryFactory
     */
    private $historyFactory;

    /**
     * @var BookmarkSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var ProcessMessage
     */
    private $processMessage;

    public function __construct(
        BookmarkSearchResultsInterfaceFactory $searchResultsFactory,
        ResourceModel\History $historyResource,
        HistoryFactory $historyFactory,
        ProcessMessage $processMessage,
        ResourceModel\CollectionFactory $historyCollectionFactory
    ) {
        $this->historyResource = $historyResource;
        $this->historyCollectionFactory = $historyCollectionFactory;
        $this->historyFactory = $historyFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->processMessage = $processMessage;
    }

    /**
     * @inheritdoc
     */
    public function create(\Amasty\Rma\Api\Data\HistoryInterface $event)
    {
        try {
            $this->historyResource->save($event);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Unable to save new rma event. Error: %1', $e->getMessage()));
        }

        return $event;
    }

    /**
     * @inheritdoc
     */
    public function getById($eventId)
    {
        /** @var \Amasty\Rma\Model\History\History $event */
        $event = $this->historyFactory->create();
        $this->historyResource->load($event, $eventId);
        if (!$event->getEventId()) {
            throw new NoSuchEntityException(
                __('Rma Event with specified ID "%1" not found.', $eventId)
            );
        }

        return $event;
    }

    /**
     * @inheritdoc
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        /** @var \Amasty\Rma\Model\History\ResourceModel\Collection $historyCollection */
        $historyCollection = $this->historyCollectionFactory->create();
        // Add filters from root filter group to the collection
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $historyCollection);
        }
        $searchResults->setTotalCount($historyCollection->getSize());
        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            $this->addOrderToCollection($sortOrders, $historyCollection);
        }
        $historyCollection->setCurPage($searchCriteria->getCurrentPage());
        $historyCollection->setPageSize($searchCriteria->getPageSize());
        $history = [];
        /** @var HistoryInterface $item */
        foreach ($historyCollection->getItems() as $item) {
            $history[] = $this->getById($item->getHistoryId());
        }
        $searchResults->setItems($history);

        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function getRequestEvents($requestId)
    {
        $collection = $this->getEmptyEventCollection()->addRequestFilter($requestId);
        $result = [];

        foreach ($collection->getItems() as $item) {
            $result[] = $this->processMessage->execute($item);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getEmptyEventModel()
    {
        return $this->historyFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function getEmptyEventCollection()
    {
        return $this->historyCollectionFactory->create();
    }

    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param FilterGroup $filterGroup
     * @param Collection  $historyCollection
     *
     * @return void
     */
    private function addFilterGroupToCollection(FilterGroup $filterGroup, Collection $historyCollection)
    {
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
            $historyCollection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
        }
    }

    /**
     * Helper function that adds a SortOrder to the collection.
     *
     * @param SortOrder[] $sortOrders
     * @param Collection  $historyCollection
     *
     * @return void
     */
    private function addOrderToCollection($sortOrders, Collection $historyCollection)
    {
        /** @var SortOrder $sortOrder */
        foreach ($sortOrders as $sortOrder) {
            $field = $sortOrder->getField();
            $historyCollection->addOrder(
                $field,
                ($sortOrder->getDirection() == SortOrder::SORT_DESC) ? 'DESC' : 'ASC'
            );
        }
    }
}
