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

namespace Magento\AdobeCommerceWebhooks\Model\Webhook;

use Magento\Framework\DataObject;

/**
 * Data Object for storing hook rules configuration
 */
class HookRule extends DataObject
{
    public const FIELD = 'field';
    public const HOOK = HookField::HOOK;
    public const VALUE = 'value';
    public const OPERATOR = 'operator';
    public const REMOVE = 'remove';
    public const XML_DEFINED = 'xml_defined';

    /**
     * Returns rule field name
     *
     * @return string
     */
    public function getField(): string
    {
        return $this->getData(self::FIELD);
    }

    /**
     * Returns parent Hook object
     *
     * @return Hook
     */
    public function getHook(): Hook
    {
        return $this->getData(self::HOOK);
    }

    /**
     * Returns rule value
     *
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->getData(self::VALUE);
    }

    /**
     * Returns rule operator
     *
     * @return string
     */
    public function getOperator(): string
    {
        return $this->getData(self::OPERATOR);
    }

    /**
     * Checks if the rule should be skipped during hook rule processing
     */
    public function shouldRemove(): bool
    {
        return (string)$this->getData(self::REMOVE) === 'true';
    }

    /**
     * Checks if the hook rule is defined in the xml configuration
     *
     * @return bool
     */
    public function isXmlDefined(): bool
    {
        return (bool)$this->getData(self::XML_DEFINED);
    }
}
