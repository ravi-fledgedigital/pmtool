<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportJson\Model;

use Magento\ImportJsonApi\Api\Data\SourceDataInterface;

/**
 * Import JSON source data model.
 */
class SourceData implements SourceDataInterface
{
    /**
     * @var string
     */
    private $entity;

    /**
     * @var string
     */
    private $behavior;

    /**
     * @var string
     */
    private $validationStrategy;

    /**
     * @var string
     */
    private $allowedErrorCount;

    /**
     * @var UnstructuredArray
     */
    private $items;

    /**
     * @var ?string
     */
    private $importImagesFileDir;

    /**
     * @var ?string
     */
    private $locale;

    /**
     * @inheritdoc
     */
    public function getEntity(): string
    {
        return $this->entity;
    }

    /**
     * @inheritdoc
     */
    public function getBehavior(): string
    {
        return $this->behavior;
    }

    /**
     * @inheritdoc
     */
    public function getValidationStrategy(): string
    {
        return $this->validationStrategy;
    }

    /**
     * @inheritdoc
     */
    public function getAllowedErrorCount(): string
    {
        return $this->allowedErrorCount;
    }

    /**
     * @inheritDoc
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @inheritDoc
     */
    public function setEntity(string $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @inheritDoc
     */
    public function setBehavior(string $behavior)
    {
        $this->behavior = $behavior;
    }

    /**
     * @inheritDoc
     */
    public function setValidationStrategy(string $validationStrategy)
    {
        $this->validationStrategy = $validationStrategy;
    }

    /**
     * @inheritDoc
     */
    public function setAllowedErrorCount(string $allowedErrorCount)
    {
        $this->allowedErrorCount = $allowedErrorCount;
    }

    /**
     * @inheritDoc
     */
    public function setItems(array $items = null)
    {
        $this->items = $items;
    }

    /**
     * @inheritDoc
     */
    public function setImportImagesFileDir(?string $dir = null)
    {
        $this->importImagesFileDir = $dir;
    }

    /**
     * @inheritDoc
     */
    public function getImportImagesFileDir(): ?string
    {
        return $this->importImagesFileDir;
    }

    /**
     * @inheritdoc
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @inheritdoc
     */
    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }
}
