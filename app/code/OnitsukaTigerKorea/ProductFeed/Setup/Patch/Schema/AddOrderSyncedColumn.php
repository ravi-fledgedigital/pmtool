<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\ProductFeed\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

class AddOrderSyncedColumn implements SchemaPatchInterface {

    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * AddRmaStockSyncedColumn constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $rmaRequestTable = $this->moduleDataSetup->getTable('sales_order');

        if ($this->moduleDataSetup->getConnection()->isTableExists($rmaRequestTable) == true){
            $this->moduleDataSetup->getConnection()->addColumn(
                $this->moduleDataSetup->getTable($rmaRequestTable),
                'order_synced',
                [
                    'type' => Table::TYPE_SMALLINT,
                    'size' => 5,
                    'nullable' => false,
                    'default' => '0',
                    'comment' => '0: Order wait sync, 1: Order Synced'
                ]
            );
        }

        $this->moduleDataSetup->endSetup();

    }
}
