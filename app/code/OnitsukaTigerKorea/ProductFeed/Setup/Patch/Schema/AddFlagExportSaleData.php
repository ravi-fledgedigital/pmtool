<?php declare(strict_types=1);

namespace OnitsukaTigerKorea\ProductFeed\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class AddFlagExportSaleData implements SchemaPatchInterface
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
        $rmaRequestTable = $this->moduleDataSetup->getTable('amasty_rma_request');

        $this->moduleDataSetup->getConnection()->addColumn(
            $this->moduleDataSetup->getTable($shipmentAttributesTable),
            'export_sale_data_flag',
            [
                'type' => Table::TYPE_SMALLINT,
                'size' => 5,
                'nullable' => false,
                'default' => '0',
                'comment' => '0: Shipment wait export, 1: Shipment exported'
            ]
        );

        $this->moduleDataSetup->getConnection()->addColumn(
            $this->moduleDataSetup->getTable($rmaRequestTable),
            'export_sale_data_flag',
            [
                'type' => Table::TYPE_SMALLINT,
                'size' => 5,
                'nullable' => false,
                'default' => '0',
                'comment' => '0: Request wait export, 1: Request exported'
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
