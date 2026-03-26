<?php

namespace OnitsukaTiger\Ninja\Setup;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    )
    {
        $setup->startSetup();

        /**
         * Create table 'ninja_access_token'
         */
        $this->createAccessToken($setup);

        /**
         * Create table 'ninja_order'
         */
        $this->createNinjaOrder($setup);

        /**
         * Create table 'ninja_status_history'
         */
        $this->createStatusHistory($setup);

        $setup->endSetup();
    }

    private function createAccessToken($setup) {
        $table = $setup->getConnection()->newTable(
            $setup->getTable(\OnitsukaTiger\Ninja\Model\ResourceModel\AccessToken::MAIN_TABLE)
        )->addColumn(
            'token_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Token ID'
        )->addColumn(
            'country_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            10,
            ['nullable' => false],
            'Country code'
        )->addColumn(
            'access_token',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '256',
            ['nullable' => false],
            'Access token'
        )->addColumn(
            'expires',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Expires'
        )->addColumn(
            'expires_in',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Expires in'
        );

        $setup->getConnection()->createTable($table);
    }

    private function createNinjaOrder($setup) {
        $table = $setup->getConnection()->newTable(
            $setup->getTable(\OnitsukaTiger\Ninja\Model\ResourceModel\Order::MAIN_TABLE)
        )->addColumn(
            'order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'shipment_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            10,
            ['nullable' => false],
            'Magento shipment ID'
        )->addColumn(
            'tracking_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            64,
            ['nullable' => false],
            'Tracking id'
        )->addColumn(
            'website_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Website id'
        )->addColumn(
            'json',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            4096,
            ['nullable' => false],
            'raw json data'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Created At'
        )->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
            'Updated At'
        );

        $setup->getConnection()->createTable($table);
    }

    private function createStatusHistory($setup) {
        $table = $setup->getConnection()->newTable(
            $setup->getTable(\OnitsukaTiger\Ninja\Model\ResourceModel\StatusHistory::MAIN_TABLE)
        )->addColumn(
            'status_history_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Status history ID'
        )->addColumn(
            'tracking_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            64,
            ['nullable' => false],
            'tracking id'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            64,
            ['nullable' => false],
            'Status'
        )->addColumn(
            'json',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            2048,
            ['nullable' => false],
            'raw json data'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Created At'
        )->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
            'Updated At'
        );
;

        $setup->getConnection()->createTable($table);
    }
}
