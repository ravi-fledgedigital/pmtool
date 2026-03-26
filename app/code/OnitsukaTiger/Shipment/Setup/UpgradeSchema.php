<?php


namespace OnitsukaTiger\Shipment\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements  \Magento\Framework\Setup\UpgradeSchemaInterface
{
    /**
     * Custom order column
     */
    const ORDER_NETSUITE_ID_FIELD = 'netsuite_internal_id';
    const POS_RECEIPT_NUMBER = 'pos_receit_number';

    public function upgrade(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.0.1') < 0) {

            // Get module table
            $tableName = $setup->getTable('shipment_extension_attributes');

            // Check if the table already exists
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $setup->getConnection()->changeColumn(
                    $setup->getTable($tableName),
                    'netsuite_fulfillment_id',
                    'netsuite_fulfillment_id',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 32,
                        'identity' => false,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => false,
                        'comment' => 'Netsuite Fulfillment Id'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            // Get module table
            $tableName = $setup->getTable('shipment_extension_attributes');

            $setup->getConnection()->addColumn(
                $setup->getTable($tableName),
                'status',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 100,
                    'nullable' => true,
                    'comment' => 'Shipment Status'
                ]
            );
        } elseif (version_compare($context->getVersion(), '1.0.3') < 0) {
            $setup->getConnection()->addForeignKey(
                $setup->getFkName(
                    'shipment_extension_attributes',
                    'shipment_id',
                    'sales_shipment',
                    'entity_id'
                ),
                'shipment_extension_attributes',
                'shipment_id',
                $setup->getTable('sales_shipment'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );
        } elseif (version_compare($context->getVersion(), '1.0.4') < 0) {
            // Get module table
            $tableName = $setup->getTable('shipment_extension_attributes');

            $setup->getConnection()->addColumn(
                $setup->getTable($tableName),
                'netsuite_internal_id',
                [
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'nullable' => true,
                    'comment' => 'Netsuite internal id'
                ]
            );
        } elseif (version_compare($context->getVersion(), '1.0.5') < 0) {
            // Get module table
            $tableName = $setup->getTable('shipment_extension_attributes');
            if (!$setup->getConnection()->tableColumnExists($tableName, self::ORDER_NETSUITE_ID_FIELD ) == true) {
                $setup->getConnection()->addColumn(
                    $setup->getTable($tableName),
                    'netsuite_internal_id',
                    [
                        'type' => Table::TYPE_TEXT,
                        'size' => 255,
                        'nullable' => true,
                        'comment' => 'Netsuite internal id'
                    ]
                );
            }
        } elseif (version_compare($context->getVersion(), '1.0.6') < 0) {
            $tableName = $setup->getTable('shipment_extension_attributes');
            if (!$setup->getConnection()->tableColumnExists($tableName, self::POS_RECEIPT_NUMBER ) == true) {
                $setup->getConnection()->addColumn(
                    $setup->getTable($tableName),
                    'pos_receipt_number',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 100,
                        'nullable' => true,
                        'comment' => 'POS Receipt Number'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.7') < 0) {

            // Get module table
            $tableName = $setup->getTable('sales_shipment');

            // Check if the table already exists
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $setup->getConnection()->addColumn(
                    $setup->getTable($tableName),
                    'is_order_synced_to_netsuite',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        'default' => 0,
                        'nullable' => false,
                        'comment' => 'Is order synced to netsuite?'
                    ]
                );
            }
        }

        $setup->endSetup();
    }
}
