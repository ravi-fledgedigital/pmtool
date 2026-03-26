<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Model\Product;

class DateAttributesMetadata
{
    /**
     * List of start date attributes
     *
     * @var array
     */
    private static $startDateKeys = [
        'news_from_date' => 'is_new',
        'special_from_date' => 'special_price',
    ];

    /**
     * List of end date attributes
     *
     * @var array
     */
    private static $endDateKeys = [
        'news_to_date' => 'is_new',
        'special_to_date' => 'special_price',
    ];

    /**
     * @var array[]
     */
    private static $emptyValues = [
        'is_new' => ['0', null],
        'special_price' => ['', null],
    ];

    /**
     * Return list of start date attributes
     *
     * @return array
     */
    public function getStartDateAttributes(): array
    {
        return array_keys(self::$startDateKeys);
    }

    /**
     * Return list of end date attributes
     *
     * @return array
     */
    public function getEndDateAttributes(): array
    {
        return array_keys(self::$endDateKeys);
    }

    /**
     * Return related attribute for specified start/end date attribute
     *
     * @param string $attribute
     * @return string
     */
    public function getRelatedAttribute(string $attribute): string
    {
        return self::$startDateKeys[$attribute] ?? self::$endDateKeys[$attribute];
    }

    /**
     * Return values that are considered empty for specified start/end date attribute's related attribute
     *
     * @param string $attribute
     * @return array
     */
    public function getRelatedAttributeEmptyValues(string $attribute): array
    {
        return self::$emptyValues[$this->getRelatedAttribute($attribute)];
    }
}
