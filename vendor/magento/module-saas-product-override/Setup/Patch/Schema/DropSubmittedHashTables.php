<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2023 Adobe
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

namespace Magento\SaaSProductOverride\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Patch schema to drop legacy "submitted hash" table
 */
class DropSubmittedHashTables implements SchemaPatchInterface
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

        $submittedHashTable = 'catalog_product_override_data_submitted_hash';
        $connection = $this->schemaSetup->getConnection();
        $tableName = $connection->getTableName($submittedHashTable);
        if ($this->schemaSetup->getTable($tableName)) {
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
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
