<?php
namespace Clickend\Kerry\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context ) {

        $setup->startSetup();

        if(version_compare($context->getVersion(), '1.0.2', '<')) {

            $setup->getConnection()
                ->addIndex(
                    'kerry_shipping_track',
                    $setup->getIdxName(
                        'kerry_shipping_track',
                        ['con_no', 'unique_id', 'r_name', 'r_telephone', 'r_email', 'service_code', 'cod_type'],
                        \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
                    ),
                    ['con_no', 'unique_id', 'r_name', 'r_telephone', 'r_email', 'service_code', 'cod_type'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
                )
            ;

            $setup->getConnection()
                ->addIndex(
                    'kerry_shipping_track_history',
                    $setup->getIdxName(
                        'kerry_shipping_track',
                        ['con_no', 'order_id', 'status', 'description', 'service_code'],
                        \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
                    ),
                        ['con_no', 'order_id', 'status', 'description', 'service_code'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
                )
            ;
        }



        $setup->endSetup();
    }
}
