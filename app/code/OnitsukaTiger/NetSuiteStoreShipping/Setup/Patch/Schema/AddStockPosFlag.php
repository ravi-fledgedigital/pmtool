<?php declare(strict_types=1);

namespace OnitsukaTiger\NetSuiteStoreShipping\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class AddStockPosFlag implements SchemaPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * UpdateShipmentAttributes constructor.
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

        $shipmentAttributesTable = $this->moduleDataSetup->getTable('shipment_extension_attributes');

        $this->moduleDataSetup->getConnection()->addColumn(
            $this->moduleDataSetup->getTable($shipmentAttributesTable),
            'stock_pos_flag',
            [
                'type' => Table::TYPE_SMALLINT,
                'size' => 5,
                'nullable' => false,
                'default' => '0',
                'comment' => '0: Wait Deduct, 1: Deducted'
            ]
        );

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
