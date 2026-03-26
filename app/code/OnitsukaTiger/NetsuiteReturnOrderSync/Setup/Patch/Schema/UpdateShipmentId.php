<?php
declare(strict_types=1);

namespace OnitsukaTiger\NetsuiteReturnOrderSync\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpdateShipmentId implements SchemaPatchInterface
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

        $requestsTable = $this->moduleDataSetup->getTable('amasty_rma_request');

        if ($this->moduleDataSetup->getConnection()->isTableExists($requestsTable) == true){
            $this->moduleDataSetup->getConnection()->addColumn(
                $this->moduleDataSetup->getTable($requestsTable),
                'shipment_increment_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => '255',
                    'nullable' => true,
                    'comment' => 'shipment increment id'
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
