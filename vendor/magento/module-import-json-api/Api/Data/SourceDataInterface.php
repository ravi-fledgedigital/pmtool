<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportJsonApi\Api\Data;

/**
 * Import JSON source data interface.
 *
 * @api
 */
interface SourceDataInterface
{
    /**
     * Get Entity
     *
     * @return string
     */
    public function getEntity(): string;

    /**
     * Get Behavior
     *
     * @return string
     */
    public function getBehavior(): string;

    /**
     * Get Validation Strategy
     *
     * @return string
     */
    public function getValidationStrategy(): string;

    /**
     * Get Allowed Error Count
     *
     * @return string
     */
    public function getAllowedErrorCount(): string;

    /**
     * Set Entity
     *
     * @param string $entity
     * @return void
     */
    public function setEntity(string $entity);

    /**
     * Set Behavior
     *
     * @param string $behavior
     * @return void
     */
    public function setBehavior(string $behavior);

    /**
     * Set Validation Strategy
     *
     * @param string $validationStrategy
     * @return void
     */
    public function setValidationStrategy(string $validationStrategy);

    /**
     *  Set Allowed Error Count
     *
     * @param string $allowedErrorCount
     * @return void
     */
    public function setAllowedErrorCount(string $allowedErrorCount);

    /**
     * Set items
     *
     * @param array|null $items
     * @return void
     */
    public function setItems(array $items = null);

    /**
     *  Get items
     *
     * @return UnstructuredArray
     */
    public function getItems();

    /**
     *  Set Import's Images File Directory
     *
     * @param string|null $dir
     * @return void
     */
    public function setImportImagesFileDir(?string $dir = null);

    /**
     *  Get Import's Images File Directory
     *
     * @return string|null
     */
    public function getImportImagesFileDir() : ?string;

    /**
     * Get import content locale
     *
     * @return string|null
     */
    public function getLocale(): ?string;

    /**
     * Set import content locale
     *
     * @param string|null $locale
     * @return void
     */
    public function setLocale(?string $locale): void;
}
