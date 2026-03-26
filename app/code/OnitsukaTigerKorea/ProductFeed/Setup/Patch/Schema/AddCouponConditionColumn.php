<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\ProductFeed\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\PatchInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

class AddCouponConditionColumn implements SchemaPatchInterface {

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

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $salesOrderTable = $this->moduleDataSetup->getTable('sales_order');

        if ($this->moduleDataSetup->getConnection()->isTableExists($salesOrderTable) == true){
            $this->moduleDataSetup->getConnection()->addColumn(
                $this->moduleDataSetup->getTable($salesOrderTable),
                'coupon_condition_serialized_rule',
                [
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'nullable' => true,
                    'comment' => 'Condition Rule Applied'
                ]
            );
        }

        $this->moduleDataSetup->endSetup();
    }
}
