<?php

namespace OnitsukaTiger\DiscountLimit\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * @package    OnitsukaTiger_DiscountLimit
 */
class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $installer->getConnection()->addColumn(
            $installer->getTable('salesrule'),
            'max_discount_amount',
            [
                'type'      => Table::TYPE_DECIMAL,
                'length'    => '12,4',
                'nullable'  => false,
                'default'   => '0.0000',
                'comment'   => 'Max Discount Amount'
            ]
        );

        $installer->endSetup();
    }
}
