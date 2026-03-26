<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare (strict_types = 1);
namespace OnitsukaTiger\MegaMenu\Setup\Patch\Data;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\Category;

/**
 * Class AdditionalClassAttribute for Category Attribute.
 */
class AdditionalClassAttribute implements DataPatchInterface
{
    /**
     * Magento\Framework\Setup\ModuleDataSetupInterface
     *
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    
    /**
     * Magento\Eav\Setup\EavSetupFactory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory          $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * Add custom category attribute as Additional class
     *
     * {@inheritdoc}
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        /**
         * Add attributes to the eav/attribute
         */
        $eavSetup->addAttribute(Category::ENTITY, 'additional_class', [
            'type'         =>   'varchar',
            'label'        =>   'Additional Class',
            'input'        =>   'text',
            'sort_order'   =>   104,
            'global'       =>   ScopedAttributeInterface::SCOPE_STORE,
            'visible'      =>   true,
            'required'     =>   false,
            'user_defined' =>   false,
            'default'      =>   null
        ]);
    }

    /**
     * Get module dependecies for patch data
     *
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Get module version for patch data
     *
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '1.0.1';
    }

    /**
     * Get module aliases for patch data
     *
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
