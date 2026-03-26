<?php
declare(strict_types=1);

namespace Seoulwebdesign\Toast\Setup\Patch\Data;

use Magento\Framework\DB\Ddl\TriggerFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RemoveInvalidLog implements DataPatchInterface
{
    /**
     * @var TriggerFactory
     */
    private TriggerFactory $triggerFactory;
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * Constructor function
     *
     * @param TriggerFactory $triggerFactory
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        TriggerFactory $triggerFactory,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->triggerFactory = $triggerFactory;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Create the new trigger for catalog_product_entity
     *
     * @return void
     */
    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();
        $sendLogTbl = $connection->getTableName('seoulwebdesign_toast_sendlog');
        $saleOrderTbl = $connection->getTableName('sales_order');
        $sql = "DELETE FROM $sendLogTbl WHERE order_id NOT IN (SELECT entity_id FROM $saleOrderTbl);";
        $connection->query($sql);
        $connection->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
