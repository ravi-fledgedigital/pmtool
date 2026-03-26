<?php
namespace OnitsukaTiger\Catalog\Model\Source\ConfigurableProduct;

use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;

/**
 * Class DefaultOptions
 * @package OnitsukaTiger\Catalog\Model\Source\ConfigurableProduct
 */
class DefaultOptions implements OptionSourceInterface
{
    /**
     * @var Config
     */
    protected $catalogConfig;

    /**
     * @var AttributeCollectionFactory
     */
    protected $attributeFactory;

    /**
     * DefaultOptions constructor.
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
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        $allAttributes = $this->getAttributes();
        foreach ($allAttributes as $attribute) {
            $options[] = [
                'label' => $attribute->getDefaultFrontendLabel(),
                'value' => $attribute->getAttributeCode()
            ];
        }

        return $options;
    }

    /**
     * @return \Magento\Framework\DataObject[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getAttributes()
    {
        $collection = $this->attributeFactory->create();
        $collection->addFieldToSelect('*')
            ->addFieldToFilter('entity_type_id', $this->catalogConfig->getEntityType(Product::ENTITY)->getEntityTypeId())->load();
        return $collection->getItems();
    }
}
