<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooksSubscriber\Api;

/**
 * Interface for webhook subscriber.
 *
 * @api
 */
interface WebhookSubscriberInterface
{
    /**
     * Subscribes to the webhook.
     *
     * @param \Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface $webhook
     * @return void
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function subscribe(\Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface $webhook): void;

    /**
     * Unsubscribes from the webhook.
     *
     * @param \Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface $webhook
     * @return void
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function unsubscribe(\Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface $webhook): void;
}
