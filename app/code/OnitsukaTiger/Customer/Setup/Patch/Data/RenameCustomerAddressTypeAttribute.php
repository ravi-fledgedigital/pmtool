<?php declare(strict_types=1);

/**
 * Patch to rename customer_address_type of Address Attribute
 *
 */
namespace OnitsukaTiger\Customer\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class RenameCustomerAddressTypeAttribute
 */
class RenameCustomerAddressTypeAttribute implements DataPatchInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * RenameCustomerAddressTypeAttribute constructor.
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory
    ) {
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
        $eavSetup->updateAttribute('customer_address', 'customer_address_type', ['attribute_code' => 'customer_location_type']);
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
        return '1.0.5';
    }
}
