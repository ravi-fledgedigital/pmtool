<?php
namespace OnitsukaTiger\Relation\Model\Source\ConfigurableProduct;

use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;

class DefaultOptions implements OptionSourceInterface
{
    /**
     * @var Config
     */
    protected Config $catalogConfig;

    /**
     * @var AttributeCollectionFactory
     */
    protected AttributeCollectionFactory $attributeFactory;

    /**
     * @param Config $catalogConfig
     * @param AttributeCollectionFactory $attributeFactory
     */
    public function __construct(
        Config $catalogConfig,
        AttributeCollectionFactory $attributeFactory
    ) {
        $this->catalogConfig = $catalogConfig;
        $this->attributeFactory = $attributeFactory;
    }

    /**
     * List All Attribute
     *
     * @return array
     * @throws LocalizedException
     */
    public function toOptionArray(): array
    {
        $options = [];
        $typeId = $this->catalogConfig->getEntityType(Product::ENTITY)->getEntityTypeId();
        $allAttributes = $this->getAttributes($typeId);

        foreach ($allAttributes as $attribute) {
            $options[] = [
                'label' => $attribute->getDefaultFrontendLabel(),
                'value' => $attribute->getAttributeCode()
            ];
        }

        return $options;
    }

    /**
     * Get Attribute Collection Items
     *
     * @param string|null $typeId
     * @return array
     */
    private function getAttributes(string|null $typeId): array
    {
        $collection = $this->attributeFactory->create();
        $collection->addFieldToSelect('*');
        $collection->addFieldToFilter('entity_type_id', $typeId);
        $collection->load();
        return $collection->getItems();
    }
}
