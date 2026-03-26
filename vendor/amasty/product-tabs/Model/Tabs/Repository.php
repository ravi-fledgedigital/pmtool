<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Tabs;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Amasty\CustomTabs\Api\TabsRepositoryInterface;
use Amasty\CustomTabs\Model\Tabs\ResourceModel\Collection;
use Amasty\CustomTabs\Model\Tabs\ResourceModel\CollectionFactory;
use Amasty\CustomTabs\Model\Tabs\ResourceModel\Tabs as TabsResource;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Repository implements TabsRepositoryInterface
{
    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var TabsFactory
     */
    private $tabsFactory;

    /**
     * @var TabsResource
     */
    private $tabsResource;

    /**
     * Model data storage
     *
     * @var array
     */
    private $tabCache;

    /**
     * @var TabsInterface[]
     */
    private $tabsByIdAndStore;

    /**
     * @var CollectionFactory
     */
    private $tabsCollectionFactory;

    public function __construct(
        SearchResultsInterfaceFactory $searchResultsFactory,
        TabsFactory $tabsFactory,
        TabsResource $tabsResource,
        CollectionFactory $tabsCollectionFactory
    ) {
        $this->searchResultsFactory = $searchResultsFactory;
        $this->tabsFactory = $tabsFactory;
        $this->tabsResource = $tabsResource;
        $this->tabsCollectionFactory = $tabsCollectionFactory;
    }

    public function save(TabsInterface $tabs): TabsInterface
    {
        try {
            if ($tabs->getTabId()) {
                $tabs = $this->getById((int)$tabs->getTabId())->addData($tabs->getData());
            }
            $this->tabsResource->save($tabs);
            unset($this->tabCache[$tabs->getTabId()]);
        } catch (\Exception $e) {
            if ($tabs->getTabId()) {
                throw new CouldNotSaveException(
                    __(
                        'Unable to save tabs with ID %1. Error: %2',
                        [$tabs->getTabId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotSaveException(__('Unable to save new tabs. Error: %1', $e->getMessage()));
        }

        return $tabs;
    }

    public function getById(int $tabId): TabsInterface
    {
        if (!isset($this->tabCache[$tabId])) {
            $tabs = $this->tabsFactory->create();
            $this->tabsResource->load($tabs, $tabId);
            if (!$tabs->getTabId()) {
                throw new NoSuchEntityException(__('Tabs with specified ID "%1" not found.', $tabId));
            }
            $this->tabCache[$tabId] = $tabs;
        }

        return $this->tabCache[$tabId];
    }

    public function getByIdAndStore(?int $tabId, int $storeId = 0): TabsInterface
    {
        $key = $tabId . '_' . $storeId;
        if (isset($this->tabsByIdAndStore[$key])) {
            return $this->tabsByIdAndStore[$key];
        }

        $collection = $this->tabsCollectionFactory->create();
        $collection->addStoreWithDefault($storeId);

        $collection->addFieldToFilter(TabsInterface::TAB_ID, ['eq' => $tabId]);
        $collection->setLimit(1);

        return $this->tabsByIdAndStore[$key] = $collection->getFirstItem();
    }

    public function getByName(string $tabName): TabsInterface
    {
        if (!isset($this->tabCache[$tabName])) {
            $tab = $this->tabsFactory->create();
            $this->tabsResource->load($tab, $tabName, 'tab_name');
            if (!$tab->getTabId()) {
                throw new NoSuchEntityException(__('Tabs with specified ID "%1" not found.', $tabName));
            }
            $this->tabCache[$tabName] = $tab;
        }

        return $this->tabCache[$tabName];
    }

    public function delete(TabsInterface $tabs): bool
    {
        try {
            $this->tabsResource->delete($tabs);
            unset($this->tabCache[$tabs->getTabId()]);
        } catch (\Exception $e) {
            if ($tabs->getTabId()) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove tabs with ID %1. Error: %2',
                        [$tabs->getTabId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove tabs. Error: %1', $e->getMessage()));
        }

        return true;
    }

    public function deleteById(int $tabId): bool
    {
        $tabsModel = $this->getById($tabId);
        $this->delete($tabsModel);

        return true;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        /** @var \Amasty\CustomTabs\Model\Tabs\ResourceModel\Collection $tabsCollection */
        $tabsCollection = $this->tabsCollectionFactory->create();

        // Add filters from root filter group to the collection
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $tabsCollection);
        }

        $searchResults->setTotalCount($tabsCollection->getSize());
        $sortOrders = $searchCriteria->getSortOrders();

        if ($sortOrders) {
            $this->addOrderToCollection($sortOrders, $tabsCollection);
        }

        $tabsCollection->setCurPage($searchCriteria->getCurrentPage());
        $tabsCollection->setPageSize($searchCriteria->getPageSize());

        $tabsData = [];
        /** @var TabsInterface $tabs */
        foreach ($tabsCollection->getItems() as $tabs) {
            $tabsData[] = $this->getById((int)$tabs->getTabId());
        }

        $searchResults->setItems($tabsData);

        return $searchResults;
    }

    public function duplicate(TabsInterface $tab): TabsInterface
    {
        $tab->setTabId(null);
        $tab->setTabName(__('Copy of ') . $tab->getTabName());
        $tab->setData('store_id', $tab->getData('stores'));
        $tab->setStatus(0);
        $tab->setCreatedAt(null);
        $tab->setUpdatedAt(null);
        $this->save($tab);

        return $tab;
    }

    /**
     * @param FilterGroup $filterGroup
     * @param Collection  $tabsCollection
     * @return void
     */
    private function addFilterGroupToCollection(FilterGroup $filterGroup, Collection $tabsCollection): void
    {
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ?: 'eq';
            $tabsCollection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
        }
    }

    /**
     * @param SortOrder[] $sortOrders
     * @param Collection  $tabsCollection
     * @return void
     */
    private function addOrderToCollection(array $sortOrders, Collection $tabsCollection): void
    {
        foreach ($sortOrders as $sortOrder) {
            $field = $sortOrder->getField();
            $tabsCollection->addOrder(
                $field,
                ($sortOrder->getDirection() == SortOrder::SORT_DESC) ? SortOrder::SORT_DESC : SortOrder::SORT_ASC
            );
        }
    }

    public function deleteOutdatedTabs(int $storeId, array $updateTabsIds): void
    {
        $this->tabsResource->deleteOutdatedTabs($storeId, $updateTabsIds);
    }
}
