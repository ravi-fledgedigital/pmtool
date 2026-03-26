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

use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Hook storage interface
 */
interface HookRepositoryInterface
{
    /**
     * Saving the hook.
     *
     * @param HookInterface $hook
     * @return HookInterface
     * @throws AlreadyExistsException
     * @throws LocalizedException
     */
    public function save(HookInterface $hook): HookInterface;

    /**
     * Deletes the hook.
     *
     * @param HookInterface $hook
     * @return bool
     * @throws CouldNotDeleteException
     * @throws LocalizedException
     */
    public function delete(HookInterface $hook): bool;

    /**
     * Loads a hook using hook ID as information about method name, method type, batch name and hook name.
     *
     * @param String $hookId
     * @return HookInterface
     * @throws LocalizedException
     */
    public function loadHook(String $hookId): HookInterface;
}
