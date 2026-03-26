<?php

namespace OnitsukaTigerKorea\Customer\Setup;

use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * Constructor
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param Config $eavConfig
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        Config $eavConfig
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
    }

    /**
     * Upgrades data for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        // Only execute for new versions
        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

            // Fetch the attribute 'gender'
            $attribute = $this->eavConfig->getAttribute('customer', 'gender');

            if ($attribute && $attribute->getId()) {
                $attributeOptions = $attribute->getSource()->getAllOptions();
                $existingOptions = array_column($attributeOptions, 'label');

                // Check if the option already exists
                $newOption = 'Prefer not to say';
                if (!in_array($newOption, $existingOptions)) {
                    $eavSetup->addAttributeOption([
                        'attribute_id' => $attribute->getId(),
                        'values' => [
                            $newOption,
                        ],
                    ]);
                }
            }
        }

        $setup->endSetup();
    }
}