<?php

namespace OnitsukaTiger\Restock\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements  \Magento\Framework\Setup\UpgradeSchemaInterface
{
    public function upgrade(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {

        $setup->startSetup();
        try {
            $table = $setup->getConnection()->newTable(
                $setup->getTable('product_alert_stock_grid')
            )->addColumn(
                'alert_stock_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Alert Stock Id'
            )->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                255,
                ['nullable' => true],
                'Customer Id'
            )->addColumn(
                'product_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                255,
                ['nullable' => false],
                'Product Id'
            )->addColumn(
                'product_image',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '255',
                ['nullable' => false],
                'Product Image'
            )->addColumn(
                'product_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '255',
                ['nullable' => false],
                'Product Name'
            )->addColumn(
                'product_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '255',
                ['nullable' => false],
                'Product Type'
            )->addColumn(
                'product_sku',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '255',
                ['nullable' => false],
                'Product Sku'
            )->addColumn(
                'product_price',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '255',
                ['nullable' => false],
                'Product Price'
            )->addColumn(
                'product_qty',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '255',
                ['nullable' => false],
                'Product Quantity'
            )->addColumn(
                'website_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '255',
                ['nullable' => false],
                'Website Id'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '255',
                ['nullable' => false],
                'Store Id'
            )->addColumn(
                'add_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Add Date At'
            )->addColumn(
                'send_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '255',
                ['nullable' => true],
                'Send Date At'
            )->addColumn(
                'send_count',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                '255',
                ['nullable' => true],
                'Send Count'
            )->addColumn(
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                '255',
                ['nullable' => true],
                'Status'
            )->addColumn(
                'alert_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                '255',
                ['nullable' => true],
                'Alert Type'
            )->addColumn(
                'last_arrival_contact_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '255',
                ['nullable' => true],
                'Last Arrival contact date'
            )->addColumn(
                'total_number_restock',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                '255',
                ['nullable' => true],
                'Total Number of Restock'
            )->addColumn(
                'restock_not_sent',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                '255',
                ['nullable' => true],
                'Restock Not Sent'
            )->addColumn(
                'restock_sent',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                '255',
                ['nullable' => true],
                'Restock Sent'
            )->setComment(
                'Product Alert Stock Grid'
            );

            $setup->getConnection()->createTable($table);
            $setup->endSetup();
        } catch (Exception $err) {
            \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info($err->getMessage());
        }
    }
}