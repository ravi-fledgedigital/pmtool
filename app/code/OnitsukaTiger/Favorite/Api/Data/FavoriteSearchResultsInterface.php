<?php
declare(strict_types=1);

namespace OnitsukaTiger\Favorite\Api\Data;

interface FavoriteSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get favorite list.
     * @return \OnitsukaTiger\Favorite\Api\Data\FavoriteInterface[]
     */
    public function getItems();

    /**
     * Set Thumbnail list.
     * @param \OnitsukaTiger\Favorite\Api\Data\FavoriteInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

