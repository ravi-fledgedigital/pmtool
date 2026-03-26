<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\Shipping\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

class CancelXmlSynced implements SchemaPatchInterface {
    const COLUMN_NAME = 'cancel_xml_synced';

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

        $salesOrderTable = $this->moduleDataSetup->getTable('sales_order');

        if ($this->moduleDataSetup->getConnection()->isTableExists($salesOrderTable)){
            if(!$this->moduleDataSetup->getConnection()->tableColumnExists($salesOrderTable,self::COLUMN_NAME)){
                $this->moduleDataSetup->getConnection()->addColumn(
                    $this->moduleDataSetup->getTable($salesOrderTable),
                    self::COLUMN_NAME,
                    [
                        'type' => Table::TYPE_SMALLINT,
                        'size' => 5,
                        'nullable' => false,
                        'default' => '0',
                        'comment' => '0: wait sync, 1: Synced'
                    ]
                );
            }
        }

        $this->moduleDataSetup->endSetup();

    }
}
