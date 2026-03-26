<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace OnitsukaTiger\MegaMenu\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;


class InstallData implements InstallDataInterface
{
    private $eavSetupFactory;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $attributeId  = $eavSetup->getAttribute(\Magento\Catalog\Model\Category::ENTITY,'is_mega_menu','attribute_id');
        if(!$attributeId){
            /**
             * Add attributes to the eav/attribute
             */
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'is_mega_menu',
                [
                    'type' => 'int',
                    'label' => 'Show Thumbnail In Menu',
                    'input' => 'select',
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'required' => true,
                    'sort_order' => 100,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'General Information',
                    'is_used_in_grid' => true,
                    'visible_on_front' => true,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => true,
                ]
            );
        }
    }
}
