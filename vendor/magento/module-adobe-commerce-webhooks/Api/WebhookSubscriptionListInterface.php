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

namespace Magento\AdobeCommerceWebhooks\Api;

use Magento\Framework\Exception\LocalizedException;

/**
 * Interface to retrieve a list of all subscribed webhooks.
 *
 * @api
 */
interface WebhookSubscriptionListInterface
{
    /**
     * Returns the list of all subscribed webhooks
     *
     * @return \Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface[]
     * @throws LocalizedException
     */
    public function getList(): array;
}
