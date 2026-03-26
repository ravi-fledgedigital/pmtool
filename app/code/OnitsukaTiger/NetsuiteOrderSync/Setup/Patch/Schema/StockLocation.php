<?php
declare(strict_types=1);

namespace OnitsukaTiger\NetsuiteOrderSync\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class StockLocation implements SchemaPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * UpdateOrderAddress constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $shipmentTable = $this->moduleDataSetup->getTable('sales_shipment');

        if ($this->moduleDataSetup->getConnection()->isTableExists($shipmentTable) == true){
            $this->moduleDataSetup->getConnection()->addColumn(
                $this->moduleDataSetup->getTable($shipmentTable),
                'stock_location',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => '100',
                    'nullable' => true,
                    'comment' => 'Stock Location'
                ]
            );
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }
}
