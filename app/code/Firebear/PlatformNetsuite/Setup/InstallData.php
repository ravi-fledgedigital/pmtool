<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Setup;

use Magento\Catalog\Model\Config as Config;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Setup\EavSetup;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as CatalogAttribute;
use Magento\Eav\Api\AttributeManagementInterface;

/**
 * Class InstallData
 * @package Firebear\PlatformNetsuite\Setup
 */
class InstallData implements InstallDataInterface
{
    const ATTRIBUTE_CODE = 'netsuite_internal_id';
    const ATTRIBUTE_GROUP = 'General';

    /**
     * @var EavSetup
     */
    private $eavSetup;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var AttributeManagementInterface
     */
    private $attributeManagement;

    /**
     * InstallData constructor.
     * @param EavSetup $eavSetup
     * @param EavConfig $eavConfig
     * @param AttributeManagementInterface $attributeManagement
     */
    public function __construct(
        EavSetup $eavSetup,
        EavConfig $eavConfig,
        Config $config,
        AttributeManagementInterface $attributeManagement
    ) {
        $this->eavSetup = $eavSetup;
        $this->eavConfig = $eavConfig;
        $this->config = $config;
        $this->attributeManagement = $attributeManagement;
    }

    /**
     * @param  ModuleDataSetupInterface $setup
     * @param  ModuleContextInterface   $context
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $this->eavSetup->addAttribute(
            AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
            self::ATTRIBUTE_CODE,
            [
                'label'      => __('Netsuite Internal Id'),
                'input'      => 'text',
                'visible'    => false,
                'required'   => false,
                'position'   => 150,
                'sort_order' => 150,
                'system'     => false
            ]
        );
        $customAttributeModel = $this->eavConfig->getAttribute(
            AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
            self::ATTRIBUTE_CODE
        );
        $customAttributeModel->setData(
            'used_in_forms',
            [
                'adminhtml_customer_address',
            ]
        );
        $customAttributeModel->save();

        $this->eavSetup->addAttribute(Product::ENTITY, self::ATTRIBUTE_CODE, [
            'label' => __('Netsuite Internal Id'),
            'visible_on_front' => 1,
            'required' => 0,
            'global' => CatalogAttribute::SCOPE_STORE
        ]);

        $entityTypeId = $this->eavSetup->getEntityTypeId(Product::ENTITY);
        $attributeSetIds = $this->eavSetup->getAllAttributeSetIds($entityTypeId);
        foreach ($attributeSetIds as $attributeSetId) {
            if ($attributeSetId) {
                $groupId = $this->config->getAttributeGroupId($attributeSetId, self::ATTRIBUTE_GROUP);
                if (!empty($groupId)) {
                    $this->attributeManagement->assign(
                        'catalog_product',
                        $attributeSetId,
                        $groupId,
                        self::ATTRIBUTE_CODE,
                        999
                    );
                }
            }
        }

        $this->eavSetup->addAttribute(
            \Magento\Catalog\Model\Category::ENTITY,
            'netsuite_internal_id',
            [
                'type' => 'varchar',
                'label' => 'Netsuite Internal ID',
                'group' => 'General Information',
                'visible' => false,
                'required' => false,
                'user_defined' => false,
            ]
        );

        $setup->endSetup();
    }
}
