<?php
namespace OnitsukaTigerKorea\Customer\Setup\Patch\Data;

use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Customer;

class OfflineStoreAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->logger = $logger;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        try {
            $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

            $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
            $attributeSetId = $customerEntity->getDefaultAttributeSetId();

            $attributeSet = $this->attributeSetFactory->create();
            $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

            $customerSetup->addAttribute(Customer::ENTITY, 'offline_store_id', [
                'type' => 'varchar',
                'label' => 'Preferred Offline Store',
                'input' => 'select',
                'required' => false,
                'visible' => false,
                'user_defined' => true,
                'unique' => true,
                'sort_order' => 1000,
                'position' => 1000,
                'system' => 0,
                'default' => '',
                'source' => \OnitsukaTigerKorea\Customer\Model\Source\OfflineStoreValue::class,
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'offline_store_id');
            $attribute->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => ['adminhtml_customer','customer_account_create','customer_account_edit']
            ]);
            $attribute->save();

            $this->logger->info("'offline_store_id' attribute has been added successfully.");
        } catch (\Exception $e) {
            $this->logger->error('Error adding customer attribute offline_store_id: ' . $e->getMessage());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
