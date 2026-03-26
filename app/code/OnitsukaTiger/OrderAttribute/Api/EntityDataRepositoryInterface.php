<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Api;

/**
 * @api
 */
interface EntityDataRepositoryInterface
{
    /**
     * Save
     *
     * @param \OnitsukaTiger\OrderAttribute\Api\Data\EntityDataInterface $entityData
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\EntityDataInterface
     */
    public function save(\OnitsukaTiger\OrderAttribute\Api\Data\EntityDataInterface $entityData);

    /**
     * Get by id
     *
     * @param int $entityId
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\EntityDataInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($entityId);

    /**
     * Delete
     *
     * @param \OnitsukaTiger\OrderAttribute\Api\Data\EntityDataInterface $entityData
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\OnitsukaTiger\OrderAttribute\Api\Data\EntityDataInterface $entityData);

    /**
     * Delete by id
     *
     * @param int $entityId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById($entityId);

    /**
     * Lists
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
