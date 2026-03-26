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
 * Data Object for storing hook field configuration
 */
class HookField extends DataObject
{
    public const NAME = 'name';
    public const HOOK = 'hook';
    public const SOURCE = 'source';
    public const CONVERTER = 'converter';
    public const REMOVE = 'remove';
    public const XML_DEFINED = 'xml_defined';

    /**
     * Returns field name
     *
     * @return string
     */
    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
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
     * Returns field source
     *
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->getData(self::SOURCE);
    }

    /**
     * Returns field converter class
     *
     * @return string|null
     */
    public function getConverter(): ?string
    {
        return $this->getData(self::CONVERTER);
    }

    /**
     * Checks if the field should be skipped during hook field processing
     */
    public function shouldRemove(): bool
    {
        return (string)$this->getData(self::REMOVE) === 'true';
    }

    /**
     * Checks if the hook field is defined in the xml configuration
     *
     * @return bool
     */
    public function isXmlDefined(): bool
    {
        return (bool)$this->getData(self::XML_DEFINED);
    }
}
