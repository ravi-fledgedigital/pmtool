<?php declare(strict_types=1);

namespace OnitsukaTiger\Shipment\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class UpdateShipmentAttributes
 * @package OnitsukaTiger\Shipment\Setup\Patch\Data
 */
class UpdateShipmentAttributes implements SchemaPatchInterface
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

        $orderAddressTable = $this->moduleDataSetup->getTable('shipment_extension_attributes');

        $this->moduleDataSetup->getConnection()->addColumn(
            $this->moduleDataSetup->getTable($orderAddressTable),
            'act_release_date',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 100,
                'nullable' => true,
                'comment' => 'Shipment Release Date'
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
