<?php

namespace Firebear\PlatformNetsuite\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Module\Manager;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * Custom column
     */
    const NETSUITE_ID_FIELD = 'netsuite_internal_id';

    /**
     * @var Manager
     */
    protected $module;

    /**
     * UpgradeSchema constructor.
     * @param Manager $module
     */
    public function __construct(
        Manager $module
    ) {
        $this->module = $module;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        if (!$context->getVersion() || version_compare($context->getVersion(), '1.5.1') < 0) {
            $installer->getConnection()
                ->addColumn(
                    $installer->getTable('sales_invoice'),
                    self::NETSUITE_ID_FIELD,
                    [
                        'type' => Table::TYPE_TEXT,
                        'size' => 255,
                        'nullable' => true,
                        'comment' => 'Netsuite internal id'
                    ]
                );
            $installer->getConnection()
                ->addColumn(
                    $installer->getTable('sales_shipment'),
                    self::NETSUITE_ID_FIELD,
                    [
                        'type' => Table::TYPE_TEXT,
                        'size' => 255,
                        'nullable' => true,
                        'comment' => 'Netsuite internal id'
                    ]
                );
            $installer->getConnection()
                ->addColumn(
                    $installer->getTable('sales_creditmemo'),
                    self::NETSUITE_ID_FIELD,
                    [
                        'type' => Table::TYPE_TEXT,
                        'size' => 255,
                        'nullable' => true,
                        'comment' => 'Netsuite internal id'
                    ]
                );
        }
        if (!$context->getVersion() || version_compare($context->getVersion(), '1.5.2') < 0) {
            if ($this->module->isEnabled('Firebear_ImportExportMsi')) {
                $installer->getConnection()->addColumn(
                    $installer->getTable('inventory_source'),
                    self::NETSUITE_ID_FIELD,
                    [
                        'type' => Table::TYPE_TEXT,
                        'size' => 255,
                        'nullable' => true,
                        'comment' => 'Netsuite internal id'
                    ]
                );
            }
        }
        $installer->endSetup();
    }
}
