<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Gthk\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface GthkRepositoryInterface
{

    /**
     * Save Gthk
     * @param \OnitsukaTiger\Gthk\Api\Data\GthkInterface $gthk
     * @return \OnitsukaTiger\Gthk\Api\Data\GthkInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \OnitsukaTiger\Gthk\Api\Data\GthkInterface $gthk
    );

    /**
     * Retrieve Gthk
     * @param string $gthkId
     * @return \OnitsukaTiger\Gthk\Api\Data\GthkInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($gthkId);

    /**
     * Retrieve Gthk matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \OnitsukaTiger\Gthk\Api\Data\GthkSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Gthk
     * @param \OnitsukaTiger\Gthk\Api\Data\GthkInterface $gthk
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \OnitsukaTiger\Gthk\Api\Data\GthkInterface $gthk
    );

    /**
     * Delete Gthk by ID
     * @param string $gthkId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($gthkId);
}

