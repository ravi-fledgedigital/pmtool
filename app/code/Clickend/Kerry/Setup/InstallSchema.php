<?php
namespace Clickend\Kerry\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use \Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{

    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();
        $context->getVersion();

        $table = $setup->getConnection()->newTable(
            $setup->getTable('kerry_shipping_track')
        )->addColumn(
            'track_id',
            Table::TYPE_INTEGER,
            null,
            [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true],
            'Trackking ID'
        )->addColumn(
            'con_no',
            Table::TYPE_TEXT,
            255,
            [],
            'Consignment No.'
        )->addColumn(
            'unique_id',
            Table::TYPE_TEXT,
            255,
            [],
            'Order ID'
        )->addColumn(
            's_name',
            Table::TYPE_TEXT,
            255,
            [],
            'Sender Name'
        )->addColumn(
            's_address',
            Table::TYPE_TEXT,
            255,
            [],
            'Sender Address'
        )->addColumn(
            's_village',
            Table::TYPE_TEXT,
            255,
            [],
            'Sender Village'
        )->addColumn(
            's_soi',
            Table::TYPE_TEXT,
            255,
            [],
            'Sender Soi'
        )->addColumn(
            's_road',
            Table::TYPE_TEXT,
            255,
            [],
            'Sender Road'
        )->addColumn(
            's_subdistrict',
            Table::TYPE_TEXT,
            255,
            [],
            'Sender Sub District'
        )->addColumn(
            's_district',
            Table::TYPE_TEXT,
            255,
            [],
            'Sender District/Amphur'
        )->addColumn(
            's_province',
            Table::TYPE_TEXT,
            255,
            [],
            'Sender Province'
        )->addColumn(
            's_zipcode',
            Table::TYPE_TEXT,
            255,
            [],
            'Sender Zipcode'
        )->addColumn(
            's_mobile1',
            Table::TYPE_TEXT,
            255,
            [],
            'Sender Mobile 1'
        )->addColumn(
            's_mobile2',
            Table::TYPE_TEXT,
            255,
            [],
            'Sender Mobile 2'
        )->addColumn(
            's_telephone',
            Table::TYPE_TEXT,
            255,
            [],
            'Sender Telephone'
        )->addColumn(
            's_email',
            Table::TYPE_TEXT,
            255,
            [],
            'Sender Email'
        )->addColumn(
            's_contact',
            Table::TYPE_TEXT,
            255,
            [],
            'Sender Contact Person'
        )->addColumn(
            'r_name',
            Table::TYPE_TEXT,
            255,
            [],
            'Recipient Name'
        )->addColumn(
            'r_address',
            Table::TYPE_TEXT,
            255,
            [],
            'Recipient Address'
        )->addColumn(
            'r_village',
            Table::TYPE_TEXT,
            255,
            [],
            'Recipient Village'
        )->addColumn(
            'r_soi',
            Table::TYPE_TEXT,
            255,
            [],
            'Recipient Soi'
        )->addColumn(
            'r_road',
            Table::TYPE_TEXT,
            255,
            [],
            'Recipient Road'
        )->addColumn(
            'r_subdistrict',
            Table::TYPE_TEXT,
            255,
            [],
            'Recipient Subdistrict'
        )->addColumn(
            'r_district',
            Table::TYPE_TEXT,
            255,
            [],
            'Recipient District'
        )->addColumn(
            'r_province',
            Table::TYPE_TEXT,
            255,
            [],
            'Recipient Province'
        )->addColumn(
            'r_zipcode',
            Table::TYPE_TEXT,
            255,
            [],
            'Recipient Zipcode'
        )->addColumn(
            'r_mobile1',
            Table::TYPE_TEXT,
            255,
            [],
            'Recipient Mobile 2'
        )->addColumn(
            'r_mobile2',
            Table::TYPE_TEXT,
            255,
            [],
            'Recipient Mobile 2'
        )->addColumn(
            'r_telephone',
            Table::TYPE_TEXT,
            255,
            [],
            'Recipient Telephone'
        )->addColumn(
            'r_email',
            Table::TYPE_TEXT,
            255,
            [],
            'Recipient Email'
        )->addColumn(
            'r_contact',
            Table::TYPE_TEXT,
            255,
            [],
            'Recipient Contact Person'
        )->addColumn(
            'special_note',
            Table::TYPE_TEXT,
            255,
            [],
            'Special Note'
        )->addColumn(
            'service_code',
            Table::TYPE_TEXT,
            255,
            [],
            'Service Code'
        )->addColumn(
            'cod_amount',
            Table::TYPE_TEXT,
            255,
            [],
            'COD Amount'
        )->addColumn(
            'cod_type',
            Table::TYPE_TEXT,
            255,
            [],
            'COD Type'
        )->addColumn(
            'tot_pkg',
            Table::TYPE_TEXT,
            255,
            [],
            'Total Package'
        )->addColumn(
            'declare_value',
            Table::TYPE_TEXT,
            255,
            [],
            'Declare Value'
        )->addColumn(
            'ref_no',
            Table::TYPE_TEXT,
            255,
            [],
            'Reference No.'
        )->addColumn(
            'action_code',
            Table::TYPE_TEXT,
            255,
            [],
            'Action Code'
        )->addColumn(
            'time_create',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT],
            'Tracking Create'
        )->addColumn(
            'last_update',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT],
            'Last Update'
        )->addIndex(
            $setup->getIdxName('kerry_shipping_track', ['con_no']),
            ['con_no']
        )->addForeignKey(
                $setup->getFkName('kerry_shipping_track', 'unique_id','sales_order', 'increment_id'),
                'unique_id',
                $setup->getTable('sales_order'),
                'increment_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Kerry Shipping Trackking'
        );
        $setup->getConnection()->createTable($table);
		
		
		
		
		 $table = $setup->getConnection()->newTable(
            $setup->getTable('kerry_shipping_track_history')
        )->addColumn(
            'history_track_id',
            Table::TYPE_INTEGER,
            null,
            [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true],
            'History ID'
        )->addColumn(
            'con_no',
            Table::TYPE_TEXT,
            255,
            [],
            'Consignment No.'
        )->addColumn(
            'order_id',
            Table::TYPE_TEXT,
            255,
            [],
            'Order ID'
        )->addColumn(
            'status',
            Table::TYPE_TEXT,
            255,
            [],
            'Shipping Status'
        )->addColumn(
            'description',
            Table::TYPE_TEXT,
            255,
            [],
            'Description'
        )->addColumn(
            'service_code',
            Table::TYPE_TEXT,
            255,
            [],
            'Service Code'
        )->addColumn(
            'create_time',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT],
            'Create Time'
        )->addColumn(
            'update_time',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT],
            'Update Time'
        )->addIndex(
            $setup->getIdxName('kerry_shipping_track_history', ['con_no']),
            ['con_no']
        )->addForeignKey(
                $setup->getFkName('kerry_shipping_track_history', 'con_no','kerry_shipping_track', 'con_no'),
                'con_no',
                $setup->getTable('kerry_shipping_track'),
                'con_no',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'History Shipping'
        );
        $setup->getConnection()->createTable($table);	
		
		
		
        $setup->endSetup();
    }
}
?>