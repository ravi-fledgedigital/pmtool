<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Setup;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

/**
 * Class Uninstall
 */
class Uninstall implements UninstallInterface
{
    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $tablesToDrop = [
            TabsInterface::STORE_TABLE_NAME,
            \Amasty\CustomTabs\Model\Tabs\ResourceModel\Tabs::TABLE_NAME,
            \Amasty\CustomTabs\Model\Tabs\ResourceModel\RuleIndex::MAIN_TABLE
        ];

        foreach ($tablesToDrop as $table) {
            $installer->getConnection()->dropTable(
                $installer->getTable($table)
            );
        }

        $installer->endSetup();
    }
}
