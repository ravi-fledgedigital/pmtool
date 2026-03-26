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

use Magento\AdobeCommerceWebhooks\Api\Data\HookFieldInterface;
use Magento\Framework\DataObject;

/**
 * Webhook field for processing webapi requests.
 */
class HookField extends DataObject implements HookFieldInterface
{
    /**
     * @inheritDoc
     */
    public function setName(string $name): HookFieldInterface
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
    public function setSource(string $source): HookFieldInterface
    {
        return $this->setData(self::SOURCE, $source);
    }

    /**
     * @inheritDoc
     */
    public function getSource(): string
    {
        return (string)$this->getData(self::SOURCE);
    }
}
