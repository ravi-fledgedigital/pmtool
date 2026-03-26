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

namespace Magento\ProductOverrideDataExporter\Plugin;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\Mview\Config;
use Magento\ProductOverrideDataExporter\Model\ViewTableMaintainer;

/**
 * Get view configuration for dimensional views
 */
class GetDimensionalViewsFromConfig
{
    private ViewTableMaintainer $viewTableMaintainer;
    private FeedIndexMetadata $metadata;

    /**
     * @param ViewTableMaintainer $viewTableMaintainer
     * @param FeedIndexMetadata $metadata
     */
    public function __construct(
        ViewTableMaintainer $viewTableMaintainer,
        FeedIndexMetadata $metadata
    ) {
        $this->viewTableMaintainer = $viewTableMaintainer;
        $this->metadata = $metadata;
    }

    /**
     * Add dimensional subscriptions to the product overrides view
     *
     * @param Config $subject
     * @param string $viewId
     * @param array|null $result
     * @return array|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetView(
        Config  $subject,
        ?array $result,
        string $viewId
    ): ?array {
        if ($viewId === $this->metadata->getFeedTableName()
            && $this->viewTableMaintainer->isDimensionModeEnabled()
        ) {
            $result['subscriptions'] = $this->viewTableMaintainer->getViewSubscriptionsForDimensionTables($result['subscriptions'] ?? []);
        }

        return $result;
    }
}
