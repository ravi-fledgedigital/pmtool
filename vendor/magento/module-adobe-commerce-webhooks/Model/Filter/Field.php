<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Model\Filter;

use Magento\AdobeCommerceWebhooks\Model\Webhook\HookField;

/**
 * Contains information about hook fields.
 */
class Field
{
    /**
     * @var array
     */
    private array $children = [];

    /**
     * @var string|null
     */
    private ?string $path = null;

    /**
     * @param string $name
     * @param Field|null $parent
     * @param HookField|null $hookField
     * @param bool $isArray
     */
    public function __construct(
        private string $name,
        private ?Field $parent = null,
        private ?HookField $hookField = null,
        private bool $isArray = false
    ) {
    }

    /**
     * Adds a child Field element.
     *
     * @param Field $field
     * @return void
     */
    public function addChildren(Field $field): void
    {
        $this->children[] = $field;
    }

    /**
     * Returns array of children Field elements.
     *
     * @return Field[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Checks if the Field has children Fields elements.
     *
     * @return bool
     */
    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    /**
     * Checks if the Field is an array.
     *
     * @return bool
     */
    public function isArray(): bool
    {
        return $this->isArray;
    }

    /**
     * Returns the parent Field element.
     *
     * @return Field|null
     */
    public function getParent(): ?Field
    {
        return $this->parent;
    }

    /**
     * Returns the Field name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns path to the current Field element.
     *
     * @return string
     */
    public function getPath(): string
    {
        if ($this->path === null) {
            $this->path = $this->getName();

            $parent = $this->getParent();
            if ($parent !== null) {
                $this->path = $parent->getPath() . '.' . $this->path;
            }
        }

        return $this->path;
    }

    /**
     * Returns the HookField associated with the Field.
     *
     * @return HookField|null
     */
    public function getHookField(): ?HookField
    {
        return $this->hookField;
    }

    /**
     * Returns the converter class name for the Field.
     *
     * @return string|null
     */
    public function getConverterClass(): ?string
    {
        return $this->hookField?->getConverter();
    }
}
