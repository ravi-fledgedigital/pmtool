<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\CatalogLabel\Setup\Patch\Schema;


use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Mirasvit\CatalogLabel\Api\Data\LabelInterface;
use Mirasvit\Core\Service\SerializeService;


class UpdateSchemaPatch implements DataPatchInterface, PatchVersionInterface
{
    private $setup;

    public function __construct(ModuleDataSetupInterface $setup)
    {
        $this->setup = $setup;
    }

    public static function getDependencies()
    {
        return [];
    }

    public static function getVersion()
    {
        return '1.0.6';
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $setup = $this->setup;

        $setup->getConnection()->startSetup();

        $this->migrateStoresToFlatTable($setup);
        $this->migrateCustomerGroupsToFlatTable($setup);
        $this->updateIndexerTable($setup);

        $setup->getConnection()->endSetup();
    }

    private function migrateStoresToFlatTable(ModuleDataSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable(LabelInterface::TABLE_NAME),
            'store_ids',
            [
                'type'     => Table::TYPE_TEXT,
                'nullable' => false,
                'comment'  => 'store_ids',
            ]
        );

        $migrateQuery = "UPDATE " . $setup->getTable(LabelInterface::TABLE_NAME)
            . " AS label SET store_ids = (SELECT GROUP_CONCAT(store_id) FROM "
            . $setup->getTable('mst_cataloglabel_label_store')
            . " AS store WHERE label.label_id = store.label_id GROUP BY store.label_id)";

        $setup->getConnection()->query($migrateQuery)->execute();
    }

    private function migrateCustomerGroupsToFlatTable(ModuleDataSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable(LabelInterface::TABLE_NAME),
            'customer_group_ids',
            [
                'type'     => Table::TYPE_TEXT,
                'nullable' => false,
                'comment'  => 'customer_group_ids',
            ]
        );

        $migrateQuery = "UPDATE " . $setup->getTable(LabelInterface::TABLE_NAME)
            . " AS label SET customer_group_ids = (SELECT GROUP_CONCAT(customer_group_id) FROM "
            . $setup->getTable('mst_cataloglabel_label_customer_group')
            . " AS customer_group WHERE label.label_id = customer_group.label_id GROUP BY customer_group.label_id)";

        $setup->getConnection()->query($migrateQuery)->execute();
    }

    private function updateIndexerTable(ModuleDataSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('mst_cataloglabel_index'),
            'display_ids',
            [
                'type'     => Table::TYPE_TEXT,
                'nullable' => false,
                'comment'  => 'display_ids',
            ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('mst_cataloglabel_index'),
            'sort_order',
            [
                'type'     => Table::TYPE_INTEGER,
                'nullable' => false,
                'default'  => 0,
                'comment'  => 'sort_order',
            ]
        );
    }
}
