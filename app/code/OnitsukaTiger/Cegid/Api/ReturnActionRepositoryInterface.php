<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Cegid\Api;

interface ReturnActionRepositoryInterface
{

    /**
     * Save ReturnAction
     * @param \OnitsukaTiger\Cegid\Api\Data\ReturnActionInterface $returnAction
     * @return \OnitsukaTiger\Cegid\Api\Data\ReturnActionInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \OnitsukaTiger\Cegid\Api\Data\ReturnActionInterface $returnAction
    ): Data\ReturnActionInterface;

    /**
     * Retrieve ReturnAction
     * @param string $returnactionId
     * @return \OnitsukaTiger\Cegid\Api\Data\ReturnActionInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($returnactionId): Data\ReturnActionInterface;

    /**
     * Retrieve ReturnAction matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \OnitsukaTiger\Cegid\Api\Data\ReturnActionSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    ): Data\ReturnActionSearchResultsInterface;

    /**
     * Delete ReturnAction
     * @param \OnitsukaTiger\Cegid\Api\Data\ReturnActionInterface $returnAction
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \OnitsukaTiger\Cegid\Api\Data\ReturnActionInterface $returnAction
    ): bool;

    /**
     * Delete ReturnAction by ID
     * @param string $returnactionId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($returnactionId): bool;
}
