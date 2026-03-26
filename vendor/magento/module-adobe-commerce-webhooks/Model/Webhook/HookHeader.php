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
 * Data Object for storing hook header configuration
 */
class HookHeader extends DataObject
{
    public const NAME = 'name';
    public const HOOK = HookField::HOOK;
    public const VALUE = 'value';
    public const RESOLVER = 'resolver';
    public const REMOVE = 'remove';
    public const XML_DEFINED = 'xml_defined';

    /**
     * Returns header name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getData(self::NAME);
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
     * Returns header value
     *
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->getData(self::VALUE);
    }

    /**
     * Returns header resolver class
     *
     * @return string|null
     */
    public function getResolver(): ?string
    {
        return $this->getData(self::RESOLVER);
    }

    /**
     * Checks if the header should be skipped during hook header processing
     */
    public function shouldRemove(): bool
    {
        return (string)$this->getData(self::REMOVE) === 'true';
    }

    /**
     * Checks if the hook header is defined in the xml configuration
     *
     * @return bool
     */
    public function isXmlDefined(): bool
    {
        return (bool)$this->getData(self::XML_DEFINED);
    }
}
