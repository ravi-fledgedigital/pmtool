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

namespace Magento\AdobeCommerceWebhooksSubscriber\Model\WebhookSubscriber;

use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Interface for batch order updating after saving webhook data.
 */
interface BatchOrderUpdaterInterface
{
    /**
     * Updates batch order after saving new webhook data.
     *
     * @param HookInterface $newHook
     * @return void
     * @thros LocalizedException
     */
    public function execute(HookInterface $newHook): void;
}
