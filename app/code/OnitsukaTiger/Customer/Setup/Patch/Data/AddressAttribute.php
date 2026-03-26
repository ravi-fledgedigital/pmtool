<?php declare(strict_types=1);

/**
 * Patch to create Customer Address Attribute
 *
 * Creates address type custom address attribute
 *
 */

namespace OnitsukaTiger\Customer\Setup\Patch\Data;

use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class AddressAttribute
 */
class AddressAttribute implements DataPatchInterface
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

        $eavSetup->addAttribute('customer_address', 'address_type', [
            'type'             => 'int',
            'input'            => 'select',
            'label'            => 'Address Type',
            'visible'          => true,
            'required'         => true,
            'user_defined'     => true,
            'system'           => false,
            'group'            => 'General',
            'global'           => true,
            'visible_on_front' => true,
            'source' => 'OnitsukaTiger\Customer\Model\Config\Source\Options',
        ]);

        $customAttribute = $this->eavConfig->getAttribute('customer_address', 'address_type');

        $customAttribute->setData(
            'used_in_forms',
            [
                'adminhtml_customer_address',
                'customer_address_edit',
                'customer_register_address'
            ]
        );
        $customAttribute->save();

        $eavSetup->addAttribute('customer_address', 'district', [
            'type'             => 'varchar',
            'input'            => 'text',
            'label'            => 'District',
            'visible'          => true,
            'required'         => true,
            'user_defined'     => true,
            'system'           => false,
            'group'            => 'General',
            'global'           => true,
            'visible_on_front' => true,
        ]);

        $customAttribute = $this->eavConfig->getAttribute('customer_address', 'district');

        $customAttribute->setData(
            'used_in_forms',
            [
                'adminhtml_customer_address',
                'customer_address_edit',
                'customer_register_address'
            ]
        );
        $customAttribute->save();


        $eavSetup->addAttribute('customer_address', 'email', [
            'type'             => 'varchar',
            'input'            => 'text',
            'label'            => 'Email',
            'visible'          => true,
            'required'         => true,
            'user_defined'     => true,
            'system'           => false,
            'group'            => 'General',
            'global'           => true,
            'visible_on_front' => true,
        ]);

        $customAttribute = $this->eavConfig->getAttribute('customer_address', 'email');

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
        return '1.0.0';
    }
}
