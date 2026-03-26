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

namespace Magento\DataExporterStatus\Service;

use Magento\DataExporter\Model\FeedMetadataPool;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;

class FeedIdentifiersProvider
{
    /**
     * @var FeedIndexMetadata[]
     */
    private array $metadataToFeedMap = [];

    /**
     * Additional identifiers for specific feeds
     *
     * @var array<string, string[]>
     */
    private array $additionalIdentifiers = [
        'categories' => [
            'name'
        ]
    ];

    /**
     * @param FeedMetadataPool $feedMetadataPool
     */
    public function __construct(private readonly FeedMetadataPool $feedMetadataPool)
    {
    }

    /**
     * Get feed item identifiers
     *
     * @param string $feed
     * @return string[]
     */
    public function getIdentifiers(string $feed): array
    {
        $metadata = $this->getMetadata($feed);
        $identifiers = $metadata->getFeedItemIdentifiers();
        unset($identifiers[$metadata->getFeedIdentity()]);
        return isset($this->additionalIdentifiers[$feed])
            ? array_merge($this->additionalIdentifiers[$feed], $identifiers)
            : $identifiers;
    }

    /**
     * Get feed metadata
     *
     * @param string $feed
     * @return FeedIndexMetadata
     */
    private function getMetadata(string $feed): FeedIndexMetadata
    {
        if (!isset($this->metadataToFeedMap[$feed])) {
            $this->metadataToFeedMap[$feed] = $this->feedMetadataPool->getMetadata($feed);
        }
        return $this->metadataToFeedMap[$feed];
    }
}
