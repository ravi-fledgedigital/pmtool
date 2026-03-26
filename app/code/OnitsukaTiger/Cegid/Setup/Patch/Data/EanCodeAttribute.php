<?php

namespace OnitsukaTiger\Cegid\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class EanCodeAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private EavSetupFactory $eavSetupFactory;

    /**
     * Add EAN Code Attribute Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * Get Dependencies
     *
     * @return array|string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

     /**
     * Save Validated Bunches
     *
     * @return $this|Attribute
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'ean_code',
            [
                'group' => 'General',
                'type' => 'varchar',
                'label' => 'EAN Code',
                'input' => 'text',
                'class' => '',
                'source' => '',
                'frontend' => '',
                'backend' => '',
                'required' => false,
                'sort_order' => 5,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'visible' => true,
                'is_html_allowed_on_front' => false,
                'visible_on_front' => false,
                'user_defined' => true,
                'used_in_product_listing' => false,
                'unique' => false
            ]
        );
    }

    /**
     * Get Aliases
     *
     * @return array|string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Get Version
     *
     * @return string
     */
    public static function getVersion(): string
    {
        return '1.0.0';
    }
}
