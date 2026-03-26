<?php

declare(strict_types=1);

namespace OnitsukaTiger\Relation\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CreateJsonAttr implements DataPatchInterface
{

    /**
     * @var EavSetupFactory
     */
    private EavSetupFactory $eavSetupFactory;

    /**
     * @var AttributeRepositoryInterface
     */
    private AttributeRepositoryInterface $attributeRepository;

    /**
     * @param EavSetupFactory $eavSetupFactory
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Recreate an attribute (changing type to text), that contains available image angles for product.
     */
    //phpcs:ignore VCQP.Methods.StaticMethod.Found
    /**
     * [apply description]
     *
     * @return [type] [description]
     */
    public function apply(): void
    {
        $eavSetup = $this->eavSetupFactory->create();
        $eavSetup->addAttribute(
            Product::ENTITY,
            'json_relation',
            [
                'group' => 'General Information',
                'type' => 'text',
                'backend' => '',
                'frontend' => '',
                'label' => 'Json Product Relation',
                'input' => 'textarea',
                'class' => '',
                'source' => '',
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'default' => '',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => true,
                'is_wysiwyg_enabled'      => false,
                'unique' => false,
            ]
        );
    }

    //phpcs:ignore VCQP.Methods.StaticMethod.Found
    /**
     * [getDependencies description]
     *
     * @return [type] [description]
     */
    public static function getDependencies(): array
    {
        return [CreateSwatchAttr::class];
    }

    /**
     * @return string[]
     */
    //phpcs:ignore VCQP.Methods.StaticMethod.Found
    public function getAliases(): array
    {
        return [];
    }
}
