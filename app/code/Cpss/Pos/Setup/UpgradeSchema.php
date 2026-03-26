<?php

namespace Cpss\Pos\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
/**
 * Description of InstallSchema
 *
 * @author dharmendra
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.4', '<')) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable('post_file_creation_time')
            )->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity ID'
            )->addColumn(
                'sequence_number',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true],
                'Sequence Number'
            )->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true],
                'Created At'
            )->addColumn(
                'file_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true],
                'Type'
            )->setComment(
                'Pos File Creation Time.'
            );

            $setup->getConnection()->createTable($table);
        }

        if (version_compare($context->getVersion(), '1.0.5', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('post_file_creation_time'),
                'is_file_uploaded',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Is File Uploaded?'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.6', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('post_file_creation_time'),
                'store_code',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Store Code'
                ]
            );
        }
    }
}
