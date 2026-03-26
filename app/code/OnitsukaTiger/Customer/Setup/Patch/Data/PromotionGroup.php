<?php declare(strict_types=1);
namespace OnitsukaTiger\Customer\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class PromotionGroupId
 */
class PromotionGroup implements DataPatchInterface
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

        $eavSetup->addAttribute(
            Customer::ENTITY,
            'promotion_group_id',
            [
                'type'             => 'varchar',
                'input'            => 'multiselect',
                'label'            => 'Promotion Group Id',
                'visible'          => true,
                'required'         => false,
                'user_defined'     => true,
                'system'           => false,
                'position'         => 10,
                'group'            => 'General',
                'global'           => true,
                'visible_on_front' => true,
                'source' => 'OnitsukaTiger\Customer\Model\Config\Source\PromotionGroup',
            ]
        );

        $promotionGroupAttribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'promotion_group_id');

        $promotionGroupAttribute->setData(
            'used_in_forms',
            ['adminhtml_customer']
        );

        $promotionGroupAttribute->save();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }
}
