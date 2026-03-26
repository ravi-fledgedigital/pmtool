<?php declare(strict_types=1);

/**
 * Patch to create Customer Address Attribute
 *
 * Creates address type custom address attribute
 *
 */

namespace OnitsukaTiger\Catalog\Setup\Patch\Data;

use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class AddressAttribute
 */
class ChildrenAttribute implements DataPatchInterface
{
    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * AddressAttribute constructor.
     *
     * @param Config              $eavConfig
     * @param EavSetupFactory     $eavSetupFactory
     */
    public function __construct(
        Config $eavConfig,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->eavConfig = $eavConfig;
        $this->eavSetupFactory = $eavSetupFactory;
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

        $eavSetup->addAttribute(\Magento\Catalog\Model\Product::ENTITY, 'default_child_product_color', [
            'group' => 'General',
            'type' => 'int',
            'backend' => '',
            'frontend' => '',
            'label' => 'Default Child Product Color',
            'input' => 'select',
            'class' => '',
            'source_model' => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'default' => '0',
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => true,
            'used_in_product_listing' => false,
            'unique' => false,
            'option' => [
                'values' => [
                    'PHANTOM/PHANTOM',
                    'PEACOAT/PEACOAT',
                    'WHITE/BLACK',
                    'CREAM/GREEN',
                    'BURGUNDY/WHITE',
                    'TAI-CHI YELLOW/BLACK',
                    'BLACK/GLACIER GREY',
                    'BLUE BELL/INDIGO BLUE',
                    'MARZIPAN/MARZIPAN',
                    'CARBON/CARBON',
                    'BLACK/BLACK',
                    'DARK GREY/BLACK',
                    'CREAM/ASICS BLUE',
                    'RICH GOLD/RICH GOLD',
                    'MIDNIGHT BLUE/MIDNIGHT BLUE',
                    'WHITE/WHITE',
                    'CREAM/BLACK',
                    'WHITE/ASICS BLUE',
                    'CREAM/PEACOAT',
                    'PEACOAT/BIRCH',
                    'PORT ROYAL/PORT ROYAL',
                    'WHITE/HUNTER GREEN',
                    'CREAM/CREAM',
                    'BLACK/FEATHER GREY',
                    'MID GREY/FEATHER GREY',
                    'RED BRICK/FEATHER GREY',
                    'SILVER/WHITE',
                    'RICH GOLD/WHITE',
                    'MID GREY/MID GREY',
                    'OATMEAL/OATMEAL',
                    'DARK SEPIA/DARK SEPIA',
                    'MID GREY/WHITE',
                    'LILAC OPAL/WHITE',
                    'CREAM/OATMEAL',
                    'CARAVAN/OATMEAL',
                    'MIDNIGHT BLUE/OATMEAL',
                    'OATMEAL/CARAVAN',
                    'DARK SEPIA/PORT ROYAL',
                    'GRIS BLUE/OATMEAL',
                    'DEEP SAPPHIRE/DEEP SAPPHIRE',
                    'ACID YELLOW/ACID YELLOW',
                    'DARK SEPIA/CARAVAN',
                    'FEATHER GREY/WHITE',
                    'HUNTER GREEN/WHITE',
                    'SAFARI KHAKI/SAFARI KHAKI',
                    'CARAVAN/MIDNIGHT BLUE',
                    'MIDNIGHT BLUE/BLACK',
                    'COFFEE/COFFEE',
                    'HUNTER GREEN/HUNTER GREEN',
                    'FEATHER GREY/GLACIER GREY',
                    'PEACOAT/AQUARIUM',
                    'GLACIER GREY/GLACIER GREY'
                ],
            ]
        ]);
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
