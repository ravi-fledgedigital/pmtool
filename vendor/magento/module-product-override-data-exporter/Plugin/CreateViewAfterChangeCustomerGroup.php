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

namespace Magento\ProductOverrideDataExporter\Plugin;

use Magento\CatalogPermissions\Model\Indexer\Category\ModeSwitcher;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\ProductOverrideDataExporter\Model\ViewTableMaintainer;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\ResourceModel\GroupRepository;

class CreateViewAfterChangeCustomerGroup
{
    private ViewTableMaintainer $viewTableMaintainer;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param ViewTableMaintainer $viewTableMaintainer
     * @param CommerceDataExportLoggerInterface $logger
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        ViewTableMaintainer $viewTableMaintainer,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->viewTableMaintainer = $viewTableMaintainer;
        $this->logger = $logger;
    }

    /**
     * Plugin that creates index tables for each customer group
     *
     * @param GroupRepository $subject
     * @param GroupInterface $result
     * @return GroupInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(GroupRepository $subject, GroupInterface $result):GroupInterface
    {
        try {
            if (class_exists(ModeSwitcher::class)) {
                $this->recreateView();
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
        return $result;
    }

    /**
     * Plugin that deletes index tables for each customer group
     *
     * @param GroupRepository $subject
     * @param bool $result
     * @param string $id
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDeleteById(GroupRepository $subject, bool $result, string $id)
    {
        try {
            if (class_exists(ModeSwitcher::class)) {
                $this->recreateView();
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
        return $result;
    }

    /**
     * RecreateView
     *
     * @return void
     */
    private function recreateView(): void
    {
        if ($this->viewTableMaintainer->isDimensionModeEnabled()) {
            $this->viewTableMaintainer->removeSubscriptions();
            $this->viewTableMaintainer->createSubscriptions();
        }
    }
}
