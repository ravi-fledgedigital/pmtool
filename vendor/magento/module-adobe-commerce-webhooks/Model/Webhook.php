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

namespace Magento\AdobeCommerceWebhooks\Model;

use Magento\AdobeCommerceWebhooks\Model\Webhook\Batch;
use Magento\Framework\DataObject;

/**
 * Webhook Data Object for storing webhook configuration
 */
class Webhook extends DataObject
{
    public const NAME = 'name';
    public const TYPE = 'type';
    public const BATCHES = 'batches';

    public const TYPE_AFTER = 'after';
    public const TYPE_BEFORE = 'before';

    public const WEBHOOK_PLUGIN = 'plugin';
    public const WEBHOOK_OBSERVER = 'observer';

    /**
     *  Returns webhook Name
     *
     * @return string
     */
    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    /**
     * Returns webhook type
     *
     * @return string
     */
    public function getType(): string
    {
        return (string)$this->getData(self::TYPE);
    }

    /**
     * Returns an array of webhook batches
     *
     * @return Batch[]
     */
    public function getBatches(): array
    {
        return $this->getData(self::BATCHES);
    }
}
