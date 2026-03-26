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

namespace Magento\CommerceBackendUix\Model\Extensions;

/**
 * Interface to fetch extensions from registry
 */
interface ExtensionsFetcherInterface
{
    public const HEADER_ACCEPT = 'Accept';
    public const HEADER_VALUE_APPLICATION_JSON = 'application/json';
    public const HEADER_AUTHORIZATION = 'Authorization';
    public const HEADER_X_API_KEY = 'x-api-key';
    public const TIMEOUT_IN_SECONDS = 10;
    public const STATUS_PUBLISHED = 'PUBLISHED';

    /**
     * Fetch extensions from registry
     *
     * @return array
     */
    public function fetch(): array;
}
