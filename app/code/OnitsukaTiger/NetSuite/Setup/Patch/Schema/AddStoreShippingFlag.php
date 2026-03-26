<?php
declare(strict_types=1);

namespace OnitsukaTiger\NetSuite\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

class AddStoreShippingFlag implements SchemaPatchInterface {

    const SHIPMENT_STORE_FLAG = 'shipment_store_synced';

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
        $shipmentExtensionAttributeTable = $this->moduleDataSetup->getTable('shipment_extension_attributes');

        if ($this->moduleDataSetup->getConnection()->isTableExists($shipmentExtensionAttributeTable) == true){
            if(!$this->moduleDataSetup->getConnection()->tableColumnExists($shipmentExtensionAttributeTable,self::SHIPMENT_STORE_FLAG)) {
                $this->moduleDataSetup->getConnection()->addColumn(
                    $this->moduleDataSetup->getTable($shipmentExtensionAttributeTable),
                    self::SHIPMENT_STORE_FLAG,
                    [
                        'type' => Table::TYPE_SMALLINT,
                        'size' => 5,
                        'nullable' => false,
                        'default' => '0',
                        'comment' => '0: Order wait sync, 1: Order Synced'
                    ]
                );
            }
        }

        $this->moduleDataSetup->endSetup();

    }
}
