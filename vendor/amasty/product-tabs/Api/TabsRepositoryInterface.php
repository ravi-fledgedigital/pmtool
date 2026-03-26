<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Api;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

/**
 * @api
 */
interface TabsRepositoryInterface
{
    /**
     * @param \Amasty\CustomTabs\Api\Data\TabsInterface $tabs
     * @return \Amasty\CustomTabs\Api\Data\TabsInterface
     */
    public function save(TabsInterface $tabs);

    /**
     * @param int $tabId
     * @return \Amasty\CustomTabs\Api\Data\TabsInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $tabId): TabsInterface;

    /**
     * @param int|null $tabId
     * @param int $storeId
     *
     * @return TabsInterface
     */
    public function getByIdAndStore(?int $tabId, int $storeId = 0): TabsInterface;

    /**
     * @param string $tabName
     * @return \Amasty\CustomTabs\Api\Data\TabsInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByName(string $tabName): TabsInterface;

    /**
     * @param \Amasty\CustomTabs\Api\Data\TabsInterface $tabs
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(TabsInterface $tabs): bool;

    /**
     * @param int $tabId
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $tabId): bool;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Amasty\CustomTabs\Api\Data\TabsSearchResultsInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * @param \Amasty\CustomTabs\Api\Data\TabsInterface $tab
     *
     * @return \Amasty\CustomTabs\Api\Data\TabsInterface
     */
    public function duplicate(TabsInterface $tab): TabsInterface;

    /**
     * @param int $storeId
     * @param int[] $updateTabsIds
     *
     * @return void
     */
    public function deleteOutdatedTabs(int $storeId, array $updateTabsIds): void;
}
