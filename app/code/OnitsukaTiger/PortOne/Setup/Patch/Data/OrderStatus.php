<?php

namespace OnitsukaTiger\PortOne\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class OrderStatus implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $installer = $this->moduleDataSetup;
        $installer->startSetup();

        $this->moduleDataSetup->getConnection()->insert(
            $this->moduleDataSetup->getTable('sales_order_status'),
            [
                'status' => 'portone_authorized',
                'label'  => 'Authorized'
            ]
        );

        $this->moduleDataSetup->getConnection()->insert(
            $this->moduleDataSetup->getTable('sales_order_status_state'),
            [
                'status'             => 'portone_authorized',
                'state'              => 'new',
                'is_default'         => 0,
                'visible_on_front'   => 1
            ]
        );

        $installer->endSetup();
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
