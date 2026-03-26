<?php declare(strict_types=1);

/**
 * Patch to create Customer Address Attribute
 *
 * Creates nickname custom address attribute
 *
 */

namespace OnitsukaTiger\Customer\Setup\Patch\Data;

use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class AddressAttribute
 */
class UpdateAddressCityAttribute implements DataPatchInterface
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
     * UpdateAddressAttribute constructor.
     * @param Config $eavConfig
     * @param EavSetupFactory $eavSetupFactory
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
     * Retrieve default entities: customer, customer_address
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDefaultEntities()
    {
        $entities = [
            'customer_address' => [
                'attributes' => [
                    'city' => [
                        'type' => 'static',
                        'label' => 'City',
                        'input' => 'text',
                        'sort_order' => 80,
                        'validate_rules' => '{"max_text_length":255,"min_text_length":1}',
                        'position' => 80,
                    ],
                ],
            ],
        ];
        return $entities;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create();

        $eavSetup->addAttribute('customer_address', 'city', [
            'type' => 'static',
            'label' => 'District',
            'input' => 'text',
            'sort_order' => 80,
            'validate_rules' => '{"max_text_length":255,"min_text_length":1}',
            'position' => 80,
            'attribute_model'  => "",
            'required'         => false,
        ]);
        $customAttribute = $this->eavConfig->getAttribute('customer_address', 'city');

        $customAttribute->setData(
            'used_in_forms',
            [
                'adminhtml_customer_address',
                'customer_address_edit',
                'customer_register_address'
            ]
        );
        $customAttribute->save();
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
        return '1.0.3';
    }
}
