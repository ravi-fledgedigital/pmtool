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

namespace Magento\AdobeCommerceWebhooksSubscriber\Model\Config;

use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterface;
use Magento\AdobeCommerceWebhooks\Model\Config\WebhookNameGenerator;

/**
 * Generates unique hook name
 */
class HookNameGenerator
{
    /**
     * @param WebhookNameGenerator $webhookNameGenerator
     */
    public function __construct(private readonly WebhookNameGenerator $webhookNameGenerator)
    {
    }

    /**
     * Generates unique webhook name for the hook object
     *
     * @param HookInterface $hook
     * @return string
     */
    public function generate(HookInterface $hook): string
    {
        return $this->webhookNameGenerator->generate($hook->getWebhookMethod(), $hook->getWebhookType());
    }
}
