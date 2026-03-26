<?php
declare(strict_types=1);

namespace OnitsukaTiger\Favorite\Api\Data;

interface FavoriteInterface
{

    const THUMBNAIL = 'Thumbnail';
    const FAVORITE_ID = 'favorite_id';
    const NAME = 'Name';
    const TYPE = 'Type';
    const SKU = 'Sku';

    /**
     * Get favorite_id
     * @return string|null
     */
    public function getFavoriteId();

    /**
     * Set favorite_id
     * @param string $favoriteId
     * @return \OnitsukaTiger\Favorite\Favorite\Api\Data\FavoriteInterface
     */
    public function setFavoriteId($favoriteId);

    /**
     * Get Thumbnail
     * @return string|null
     */
    public function getThumbnail();

    /**
     * Set Thumbnail
     * @param string $thumbnail
     * @return \OnitsukaTiger\Favorite\Favorite\Api\Data\FavoriteInterface
     */
    public function setThumbnail($thumbnail);

    /**
     * Get Name
     * @return string|null
     */
    public function getName();

    /**
     * Set Name
     * @param string $name
     * @return \OnitsukaTiger\Favorite\Favorite\Api\Data\FavoriteInterface
     */
    public function setName($name);

    /**
     * Get Type
     * @return string|null
     */
    public function getType();

    /**
     * Set Type
     * @param string $type
     * @return \OnitsukaTiger\Favorite\Favorite\Api\Data\FavoriteInterface
     */
    public function setType($type);

    /**
     * Get Sku
     * @return string|null
     */
    public function getSku();

    /**
     * Set Sku
     * @param string $sku
     * @return \OnitsukaTiger\Favorite\Favorite\Api\Data\FavoriteInterface
     */
    public function setSku($sku);
}

