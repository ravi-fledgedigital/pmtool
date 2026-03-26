<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2025 Adobe
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

namespace Magento\AdobeCommerceWebhooks\Model\Data;

use Magento\AdobeCommerceWebhooks\Api\Data\HookHeaderInterface;
use Magento\Framework\DataObject;

/**
 * Webhook header for processing webapi requests.
 */
class HookHeader extends DataObject implements HookHeaderInterface
{
    /**
     * @inheritDoc
     */
    public function setName(string $name): HookHeaderInterface
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    /**
     * @inheritDoc
     */
    public function setValue(string $value): HookHeaderInterface
    {
        return $this->setData(self::VALUE, $value);
    }

    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        return (string)$this->getData(self::VALUE);
    }
}
