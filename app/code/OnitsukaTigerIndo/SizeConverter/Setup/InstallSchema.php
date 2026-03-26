<?php

namespace OnitsukaTigerIndo\SizeConverter\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (!$installer->tableExists('onitsukatigerindo_sizeconverter')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('onitsukatigerindo_sizeconverter')
            )
            ->addColumn(
                'size_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Size ID'
            )
            ->addColumn(
                'english_size',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'English Size'
            )
            ->addColumn(
                'euro_size',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Euro Size'
            )
            ->setComment('Size Converter Table');

            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
