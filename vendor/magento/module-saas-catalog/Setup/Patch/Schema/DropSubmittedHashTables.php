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

namespace Magento\SaaSCatalog\Setup\Patch\Schema;

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

        $tableList = [
            'catalog_product_data_submitted_hash',
            'catalog_product_attribute_data_submitted_hash'
        ];
        $connection = $this->schemaSetup->getConnection();

        foreach ($tableList as $tableName) {
            $tableName = $this->schemaSetup->getTable($tableName);
            if ($connection->isTableExists($tableName)) {
                $connection->dropTable($tableName);
            }
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
