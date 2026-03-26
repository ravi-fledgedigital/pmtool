<?php

namespace OnitsukaTigerKorea\ProductFeed\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class AddTelephoneColumn implements SchemaPatchInterface
{
    private $moduleDataSetup;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $tableName = $this->moduleDataSetup->getTable('sales_order_grid');
        $keyName = 'FTI_60AD395789F79B09CAE576E14446462D';

        if ($this->moduleDataSetup->getConnection()->isTableExists($tableName) == true) {
            $indexList = $this->moduleDataSetup->getConnection()->getIndexList($tableName);
            foreach ($indexList as $index) {
                if ($index['KEY_NAME'] == $keyName) {
                    $this->moduleDataSetup->getConnection()->dropIndex($tableName, $keyName);
                }
            }
        }

        $this->moduleDataSetup->endSetup();
    }
}
