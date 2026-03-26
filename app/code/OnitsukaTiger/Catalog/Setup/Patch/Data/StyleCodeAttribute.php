<?php declare(strict_types=1);

/**
 * Patch to create Customer Address Attribute
 *
 * Creates address type custom address attribute
 *
 */

namespace OnitsukaTiger\Catalog\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeManagementInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\Config as ProductConfig;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class AddressAttribute
 */
class StyleCodeAttribute implements DataPatchInterface
{
    /**
     * General attribute group
     */
    const GENERAL_GROUP = 'General';

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var ProductConfig
     */
    private $productConfig;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var AttributeManagementInterface
     */
    private $attributeManagement;

    /**
     * StyleCodeAttribute constructor.
     * @param EavConfig $eavConfig
     * @param ProductConfig $productConfig
     * @param EavSetupFactory $eavSetupFactory
     * @param AttributeManagementInterface $attributeManagement
     */
    public function __construct(
        EavConfig $eavConfig,
        ProductConfig $productConfig,
        EavSetupFactory $eavSetupFactory,
        AttributeManagementInterface $attributeManagement
    ) {
        $this->eavConfig = $eavConfig;
        $this->productConfig = $productConfig;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->attributeManagement = $attributeManagement;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create();

        $eavSetup->addAttribute(\Magento\Catalog\Model\Product::ENTITY, 'style_code', [
            'type' => 'varchar',
            'label' => 'Style Code',
            'input' => 'text',
            'class' => '',
            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'searchable' => false,
            'comparable' => false,
            'visible_on_front' => true,
            'used_in_product_listing' => false,
            'unique' => false,
            'attribute_set_id' => 'Default',
        ]);

        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
        $attributeSetIds = $eavSetup->getAllAttributeSetIds($entityTypeId);
        foreach ($attributeSetIds as $attributeSetId) {
            if ($attributeSetId) {
                $groupId = $this->productConfig->getAttributeGroupId($attributeSetId, self::GENERAL_GROUP);
                if (empty($groupId)) {
                    continue;
                }
                $this->attributeManagement->assign(Product::ENTITY, $attributeSetId, $groupId, 'style_code', 0);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '1.0.0';
    }
}
