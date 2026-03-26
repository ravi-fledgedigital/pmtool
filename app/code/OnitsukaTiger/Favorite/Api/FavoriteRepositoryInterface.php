<?php
declare(strict_types=1);

namespace OnitsukaTiger\Favorite\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface FavoriteRepositoryInterface
{

    /**
     * Save favorite
     * @param \OnitsukaTiger\Favorite\Api\Data\FavoriteInterface $favorite
     * @return \OnitsukaTiger\Favorite\Api\Data\FavoriteInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \OnitsukaTiger\Favorite\Api\Data\FavoriteInterface $favorite
    );

    /**
     * Retrieve favorite
     * @param string $favoriteId
     * @return \OnitsukaTiger\Favorite\Api\Data\FavoriteInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($favoriteId);

    /**
     * Retrieve favorite matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \OnitsukaTiger\Favorite\Api\Data\FavoriteSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete favorite
     * @param \OnitsukaTiger\Favorite\Api\Data\FavoriteInterface $favorite
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \OnitsukaTiger\Favorite\Api\Data\FavoriteInterface $favorite
    );

    /**
     * Delete favorite by ID
     * @param string $favoriteId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($favoriteId);
}

