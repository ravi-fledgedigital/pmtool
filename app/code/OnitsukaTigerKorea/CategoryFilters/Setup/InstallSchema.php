<?php

namespace OnitsukaTigerKorea\CategoryFilters\Setup;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * Create a new table category_filters and category_filters_relation_row
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     */
    public function install(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();
        if (!$installer->tableExists("category_filters")) {
            $table = $installer
                ->getConnection()
                ->newTable($installer->getTable("category_filters"))
                ->addColumn(
                    "filter_id",
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        "identity" => true,
                        "nullable" => false,
                        "primary" => true,
                        "unsigned" => true,
                    ],
                    "Filter ID"
                )
                ->addColumn(
                    "category_id",
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        "nullable" => false,
                    ],
                    "Category ID"
                )
                ->addColumn(
                    "category_name",
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ["nullable" => false, "defalut" => ""],
                    "Category Name"
                )
                ->addColumn(
                    "status",
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        "nullable" => false,
                    ],
                    "Status"
                )
                ->addColumn(
                    "created_at",
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ["nullable" => true],
                    "Created At"
                )
                ->addColumn(
                    "updated_at",
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ["nullable" => true],
                    "Updated At"
                )
                ->setComment("Category Filters");
            $installer->getConnection()->createTable($table);
        }
        if (!$installer->tableExists("category_filters_relation_row")) {
            $table = $installer
                ->getConnection()
                ->newTable(
                    $installer->getTable("category_filters_relation_row")
                )
                ->addColumn(
                    "entity_id",
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        "identity" => true,
                        "unsigned" => true,
                        "nullable" => false,
                        "primary" => true,
                    ],
                    "Entity ID"
                )
                ->addColumn(
                    "filter_id",
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        "nullable" => false,
                        "unsigned" => true,
                    ],
                    "Filter ID"
                )
                ->addColumn(
                    "category_name",
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ["nullable" => false, "defalut" => ""],
                    "Category Name"
                )
                ->addColumn(
                    "category_id",
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        "nullable" => false,
                    ],
                    "Category ID"
                )
                ->addColumn(
                    "parent_category_id",
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        "nullable" => false,
                    ],
                    "Parent Category ID"
                )
                ->addForeignKey(
                    $installer->getFkName(
                        "category_filters_relation_row",
                        "filter_id",
                        "category_filters",
                        "filter_id"
                    ),
                    "filter_id",
                    $installer->getTable("category_filters"),
                    "filter_id",
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->setComment("Category Filters Relation Row");
            $installer->getConnection()->createTable($table);
        }
        $installer->endSetup();
    }
}
