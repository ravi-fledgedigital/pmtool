<?php
namespace OnitsukaTiger\PreOrders\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    private $eavSetupFactory;

    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'pre_order_status',
            [
                'group' => 'General',
                'label' => 'Enable Pre-Order',
                'type' => 'int',
                'input' => 'boolean',
                'backend' => '',
                'frontend' => '',
                'class' => '',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'default' => '0',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => 'simple,configurable,grouped,virtual,bundle,downloadable'
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'pre_order_note',
            [
                'group' => 'General',
                'type' => 'text',
                'backend' => '',
                'frontend' => '',
                'label' => 'Pre-Order Note',
                'input' => 'text',
                'class' => '',
                'source' => '',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'visible' => true,
                'required' => true,
                'user_defined' => false,
                'default' => '',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => 'simple,configurable,grouped,virtual,bundle,downloadable'
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'start_date_preorder',
            [
                'group' => 'General',
                'label' => 'Start Date',
                'type' => 'datetime',
                'input' => 'date',
                'input_renderer' => 'Velanapps\Test\Block\Adminhtml\Form\Element\Datetime',
                'class' => 'validate-date',
                'backend' => 'Magento\Catalog\Model\Attribute\Backend\Startdate',
                'frontend' => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                'required' => false,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'visible' => true,
                'user_defined' => true,
                'default' => '',
                'searchable' => true,
                'filterable' => true,
                'filterable_in_search' => true,
                'visible_in_advanced_search' => true,
                'comparable' => false,
                'required' => true,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => false
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'end_date_preorder',
            [
                'group' => 'General',
                'label' => 'End Date',
                'type' => 'datetime',
                'input' => 'date',
                'input_renderer' => 'Velanapps\Test\Block\Adminhtml\Form\Element\Datetime',
                'class' => 'validate-date',
                'frontend' => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                'required' => false,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'visible' => true,
                'user_defined' => true,
                'default' => '',
                'searchable' => true,
                'filterable' => true,
                'filterable_in_search' => true,
                'visible_in_advanced_search' => true,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => false
            ]
        );
  }
}