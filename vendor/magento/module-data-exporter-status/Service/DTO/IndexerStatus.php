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

namespace Magento\DataExporterStatus\Service\DTO;

use Magento\Framework\Indexer\IndexerInterface;

/**
 * @api
 */
class IndexerStatus
{
    /**
     * @param IndexerInterface $indexer
     * @param int $changelogBacklog
     * @param array $changelogIds
     * @param string|null $changelogLastUpdated
     */
    public function __construct(
        private readonly IndexerInterface $indexer,
        private readonly int $changelogBacklog,
        private readonly array $changelogIds,
        private readonly ?string $changelogLastUpdated,
    ) {
    }

    /**
     * Get the changelog backlog count
     */
    public function getChangelogBacklog(): int
    {
        return $this->changelogBacklog;
    }

    /**
     * Get the changelog IDs
     */
    public function getChangelogIds(): array
    {
        return $this->changelogIds;
    }

    /**
     * Get the indexer instance
     */
    public function getIndexer(): IndexerInterface
    {
        return $this->indexer;
    }

    /**
     * Get the last updated timestamp of the changelog
     */
    public function getChangelogLastUpdated(): ?string
    {
        return $this->changelogLastUpdated;
    }
}
