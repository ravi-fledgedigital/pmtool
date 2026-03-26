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
use Magento\CatalogPermissions\Model\Indexer\TableMaintainer;
use Magento\ProductOverrideDataExporter\Model\ViewTableMaintainer;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Config\App\Config\Type\System;
use Magento\Framework\App\ObjectManager;

/**
 * Create mysql view table for index after enabled dimension feature only in read mode
 */
class CreateViewAfterTableMaintenance
{
    private ViewTableMaintainer $viewTableMaintainer;
    private CommerceDataExportLoggerInterface $logger;
    private System $systemConfig;

    /**
     * @param ViewTableMaintainer $viewTableMaintainer
     * @param CommerceDataExportLoggerInterface $logger
     * @param System|null $systemConfig
     */
    public function __construct(
        ViewTableMaintainer $viewTableMaintainer,
        CommerceDataExportLoggerInterface $logger,
        ?System $systemConfig = null
    ) {
        $this->viewTableMaintainer = $viewTableMaintainer;
        $this->logger = $logger;
        $this->systemConfig = $systemConfig
            ?: ObjectManager::getInstance()->get(System::class);
    }

    /**
     * Drop view
     *
     * @param TableMaintainer $subject
     * @param string $currentMode
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeCreateTablesForCurrentMode(TableMaintainer $subject, string $currentMode): void
    {
        try {
            if (class_exists(ModeSwitcher::class) && $currentMode === ModeSwitcher::DIMENSION_NONE) {
                $this->viewTableMaintainer->removeSubscriptions();
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }

    /**
     * Create view
     *
     * @param TableMaintainer $subject
     * @param bool $result
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDropOldData(TableMaintainer $subject, bool $result): void
    {
        try {
            if (class_exists(ModeSwitcher::class)) {
                //Clear config to load actual configuration
                $this->systemConfig->clean();
                $this->viewTableMaintainer->createSubscriptions();
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
