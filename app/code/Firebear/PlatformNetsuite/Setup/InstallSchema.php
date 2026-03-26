<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class InstallSchema
 * @package Firebear\PlatformNetsuite\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Custom order column
     */
    const ORDER_NETSUITE_ID_FIELD = 'netsuite_internal_id';

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            self::ORDER_NETSUITE_ID_FIELD,
            [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'nullable' => true,
                'comment' => 'Netsuite internal id'
            ]
        );
        $installer->endSetup();
    }
}
