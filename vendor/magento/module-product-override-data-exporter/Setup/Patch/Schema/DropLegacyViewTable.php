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

namespace Magento\ProductOverrideDataExporter\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * This patch drops the legacy view table used for aggregate permissions collection.
 *
 * This table obsolete since the introduction of the new logic in dimensional index select (MDEE-654)
 */
class DropLegacyViewTable implements SchemaPatchInterface
{
    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(
        private readonly SchemaSetupInterface $schemaSetup
    ) {}

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->schemaSetup->startSetup();

        $tableName = $this->schemaSetup->getTable('magento_catalogpermissions_index_product_read');

        $connection = $this->schemaSetup->getConnection();
        if ($connection->isTableExists($tableName)) {
            $connection->dropTable($tableName);
        }

        $this->schemaSetup->endSetup();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [DropLegacyTables::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
