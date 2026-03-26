<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
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

namespace Magento\CommerceBackendUix\Api;

/**
 * Logs repository interface for Admin UI SDK logs storage.
 *
 * @api
 */
interface LogRepositoryInterface
{
    /**
     * Saving the log to database.
     *
     * @param \Magento\CommerceBackendUix\Api\Data\LogInterface $request
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(\Magento\CommerceBackendUix\Api\Data\LogInterface $request): void;
}
