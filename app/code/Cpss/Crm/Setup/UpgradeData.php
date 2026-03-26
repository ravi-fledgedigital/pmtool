<?php

namespace Cpss\Crm\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Config;

class UpgradeData implements UpgradeDataInterface
{
    protected $customerSetupFactory;
    protected $eavConfig;
    protected $eavSetupFactory;

    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        Config $eavConfig,
        EavSetupFactory $eavSetupFactory
    )
    {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

            $customerSetup->addAttribute(
                'customer',
                'is_agreed',
                [
                    'label' => 'Custom Attribute',
                    'required' => 0,
                    'visible' => 0, //<-- important, to display the attribute in customer edit
                    'input' => 'text',
                    'type' => 'static',
                    'system' => 0, // <-- important, to have the value be saved
                    'position' => 40,
                    'sort_order' => 40
                ]
            );

            $setup->endSetup();
        }
    }
}
