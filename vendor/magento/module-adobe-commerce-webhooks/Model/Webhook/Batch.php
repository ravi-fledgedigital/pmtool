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

use Magento\AdobeCommerceWebhooks\Model\Webhook;
use Magento\Framework\DataObject;

/**
 * Data Object for storing webhook batch configuration
 */
class Batch extends DataObject
{
    public const ORDER = 'order';
    public const NAME = 'name';
    public const HOOKS = 'hooks';
    public const WEBHOOK = 'webhook';

    /**
     * Return batch order
     *
     * @return int
     */
    public function getOrder(): int
    {
        return (int)$this->getData(self::ORDER);
    }

    /**
     * Return batch name
     *
     * @return string
     */
    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    /**
     * Returns hooks in the batch
     *
     * @return Hook[]
     */
    public function getHooks(): array
    {
        return $this->getData(self::HOOKS);
    }

    /**
     * Return Webhook for the batch
     *
     * @return Webhook
     */
    public function getWebhook(): Webhook
    {
        return $this->getData(self::WEBHOOK);
    }
}
