<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace OnitsukaTiger\NetsuiteOrderSync\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class InstallSchema
 * @package OnitsukaTiger\NetsuiteOrderSync\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Custom order column
     */
    const ORDER_NETSUITE_DETAIL_ID_FIELD = 'netsuite_order_item_detail';

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
            self::ORDER_NETSUITE_DETAIL_ID_FIELD,
            [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'nullable' => true,
                'comment' => 'Netsuite Detail Order Item Json '
            ]
        );
        $installer->endSetup();
    }
}
