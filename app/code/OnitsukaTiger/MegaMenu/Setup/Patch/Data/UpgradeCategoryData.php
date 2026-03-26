<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace OnitsukaTiger\MegaMenu\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;


class UpgradeCategoryData implements DataPatchInterface
{
    private $eavSetupFactory;
    private $setup;

    /**
     * UpgradeCategoryData constructor.
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $setup
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $setup
    )
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->setup = $setup;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->setup]);
        /**
         * Add attributes to the eav/attribute
         */
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Category::ENTITY,
            'show_all_items',
            [
                'type' => 'int',
                'label' => 'Show All Items Link in Menu',
                'input' => 'select',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'required' => true,
                'sort_order' => 101,
                'default'  => '0',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'General Information',
                'is_used_in_grid' => true,
                'visible_on_front' => true,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => true,
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Category::ENTITY,
            'show_all_first',
            [
                'type' => 'int',
                'label' => 'Show All Category on top In Menu',
                'input' => 'select',
                'default'  => '0',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'required' => true,
                'sort_order' => 102,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'General Information',
                'is_used_in_grid' => true,
                'visible_on_front' => true,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => true,
            ]
        );
    }
    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getVersion()
    {
        return '1.0.0';
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
