<?php 
namespace OnitsukaTiger\Rma\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * @codeCoverageIgnore
 * phpcs:ignoreFile
 */

class UpgradeSchema implements UpgradeSchemaInterface {
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $connection = $setup->getConnection();
            $connection->addColumn(
                $setup->getTable('amasty_rma_request'),
                'cegid_synced_error_message',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'default' => '',
                    'comment' => 'Cegid Synced Error Message'
                ]
            );
        }

        $setup->endSetup();
    }
}