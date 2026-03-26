<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Table as SourceTable;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CreateProductGroupAttr implements DataPatchInterface
{
    private EavSetupFactory $eavSetupFactory;

    /**
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * Creates an attribute, that contains available image angles for product.
     */
    public function apply(): void
    {
        $attributeCode = 'product_group';

        $eavSetup = $this->eavSetupFactory->create();
        if ($eavSetup->getAttribute(Product::ENTITY, $attributeCode, 'attribute_code')) {
            return;
        }

        $eavSetup->addAttribute(
            Product::ENTITY,
            $attributeCode,
            [
                'group' => 'General Information',
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Product Group',
                'input' => 'select',
                'class' => '',
                'source_model' => SourceTable::class,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'default' => '',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => true,
                'unique' => false,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
            ]
        );
    }

    /**
     * @return string[]
     */
    //phpcs:ignore VCQP.Methods.StaticMethod.Found
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
