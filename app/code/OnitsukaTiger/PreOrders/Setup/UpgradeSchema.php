<?php
namespace OnitsukaTiger\PreOrders\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        $orderTable = 'sales_order';
        $orderGridTable = 'sales_order_grid';
        if (version_compare($context->getVersion(), '1.0.1', '<')){
            //Order table
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($orderTable),
                    'is_pre_order',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'default' => 0,
                        'comment' => 'is_pre_order'
                    ]
                );
                //Order Grid table
            $setup->getConnection()
            ->addColumn(
                $setup->getTable($orderGridTable),
                'is_pre_order',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'default' => 0,
                    'comment' => 'is_pre_order'
                ]
            );
        }
        $orderItemTable = 'sales_order_item';
        if (version_compare($context->getVersion(), '1.0.2', '<')){
            $setup->getConnection()
            ->addColumn(
                $setup->getTable($orderItemTable),
                'launch_date',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    'nullable' => true,
                    'default' => NULL,
                    'comment' => 'Pre order Start Date'
                ]
            );
        }
        $setup->endSetup();
    }
}
