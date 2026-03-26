<?php
namespace OnitsukaTiger\NetsuiteReturnOrderSync\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Custom order column
     */
    const NETSUITE_INTERNAL_RMA_REQUEST = 'netsuite_internal_rma_request';

    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context ) {
        $installer = $setup;
        $installer->startSetup();

        if(version_compare($context->getVersion(), '1.1.0', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('amasty_rma_request'),
                self::NETSUITE_INTERNAL_RMA_REQUEST,
                [
                    'type' => Table::TYPE_SMALLINT,
                    'size' => 5,
                    'nullable' => false,
                    'default' => '0',
                    'comment' => '0: Request wait sync, 1: Request Synced'
                ]
            );
        }

        $installer->endSetup();
    }
}
