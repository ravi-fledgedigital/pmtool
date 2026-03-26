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

namespace Magento\AdobeCommerceWebhooksSubscriber\Model;

use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterface;

/**
 * Generates unique ID based on hook configuration
 */
class HookIdGenerator
{
    public const ID_DELIMITER = '__';

    /**
     * Generates unique ID based on hook configuration
     *
     * @param string $webhookMethod
     * @param string $webhookType
     * @param string $batchName
     * @param string $hookName
     * @return string
     */
    public function generate(string $webhookMethod, string $webhookType, string $batchName, string $hookName): string
    {
        return implode(
            self::ID_DELIMITER,
            [
                $webhookMethod,
                $webhookType,
                $batchName,
                $hookName
            ]
        );
    }

    /**
     * Generates unique ID for the hook object
     *
     * @param HookInterface $hook
     * @return string
     */
    public function generateForHook(HookInterface $hook): string
    {
        return $this->generate(
            $hook->getWebhookMethod(),
            $hook->getWebhookType(),
            $hook->getBatchName(),
            $hook->getHookName()
        );
    }
}
