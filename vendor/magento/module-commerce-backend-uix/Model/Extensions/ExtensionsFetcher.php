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
 * ExtensionsFetcher class to fetch extensions from extensions registry
 */
class ExtensionsFetcher
{
    /**
     * @param ExtensionsFetcherInterface[] $extensionFetchers
     */
    public function __construct(
        private array $extensionFetchers
    ) {
    }

    /**
     * Fetch extensions from registries
     *
     * @return array
     */
    public function fetch(): array
    {
        $result = [];
        foreach ($this->extensionFetchers as $extensionFetcher) {
            $result[] = $extensionFetcher->fetch();
        }
        return array_merge([], ...$result);
    }
}
