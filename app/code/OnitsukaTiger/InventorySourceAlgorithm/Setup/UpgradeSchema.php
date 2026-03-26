<?php
namespace OnitsukaTiger\InventorySourceAlgorithm\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Custom order column
     */
    const ORDER_VERIFY_REALLOCATE = 'order_verify_reallocate';
    const ORDER_LAST_TIME_REJECT = 'last_time_reject';
    const LOCATION_REJECT       = 'location_reject';


    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context ) {

        $installer = $setup;
        $installer->startSetup();
        if(version_compare($context->getVersion(), '1.1.0', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'),
                self::ORDER_VERIFY_REALLOCATE,
                [
                    'type' => Table::TYPE_INTEGER,
                    'size' => 255,
                    'nullable' => false,
                    'default' => '0',
                    'comment' => 'number of reallocation'
                ]
            );
        }

        if(version_compare($context->getVersion(), '1.2.0', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'),
                self::ORDER_LAST_TIME_REJECT,
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'nullable' => false,
                    'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT,
                    'comment' => 'Time last shipment reject'
                ]
            );
        }

        if(version_compare($context->getVersion(), '1.3.0', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'),
                self::LOCATION_REJECT,
                [
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'nullable' => true,
                    'comment' => 'location reject'
                ]
            );
        }


        $installer->endSetup();
    }
}
