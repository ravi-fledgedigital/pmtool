<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Catalog\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;


/**
 * Class AddressAttribute
 */
class AddColorCodeProductAttribute implements DataPatchInterface
{
    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $productAttributeRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory
     */
    private  $attributeFactory;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;


    public function __construct(
        ProductAttributeRepositoryInterface $productAttributeRepository,
        \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory,
        EavSetupFactory $eavSetupFactory
    )
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->attributeFactory = $attributeFactory;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create();
        $attribute = $this->attributeFactory->create();
        $entityType = $eavSetup->getEntityTypeId(ProductAttributeInterface::ENTITY_TYPE_CODE);
        if (!$attribute->loadByCode($entityType, 'color_code')->getAttributeId()) {
            $attribute->setData(
                [
                    'frontend_label' => 'Color Code',
                    'entity_type_id' => $entityType,
                    'frontend_input' => 'select',
                    'backend_type' => 'int',
                    'is_required' => '0',
                    'attribute_code' => 'color_code',
                    'is_global' => '1',
                    'is_user_defined' => 1,
                    'is_unique' => '0',
                    'is_searchable' => '1',
                    'is_comparable' => '1',
                    'is_filterable' => '0',
                    'is_filterable_in_search' => '0',
                    'is_used_for_promo_rules' => '0',
                    'is_html_allowed_on_front' => '1',
                    'used_in_product_listing' => '1',
                    'used_for_sort_by' => '0',
                    'swatch_input_type' => 'visual',
                    'update_product_preview_image' =>'1',
                    'use_product_image_for_swatch' =>'1',
                    'is_visible_on_front' => 1,
                    'is_visible_in_advanced_search' => 1,
                    'is_used_in_grid' => '1',
                    'is_visible_in_grid' => '1',
                    'is_filterable_in_grid' => '1'
                ]
            );
        }

        $this->productAttributeRepository->save($attribute);
        $eavSetup->addAttributeToGroup(
            ProductAttributeInterface::ENTITY_TYPE_CODE,
            'Default',
            'General',
            $attribute->getId()
        );
    }
}
