<?php
namespace OnitsukaTiger\Sorting\Model\Config\Source;

class Attribute implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    private $_attributeFactory;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeFactory

    ){
        $this->_attributeFactory = $attributeFactory;
    }
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = $this->getAllAttributes();
        $result = [];
        foreach ($attributes as $attribute){
            $result[] = [
                'value' => $attribute->getAttributeCode(),
                'label' => $attribute->getFrontendLabel()
            ];
        }
        return $result;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = $this->getAllAttributes();
        $result = [];
        foreach ($attributes as $attribute){
            $result[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
        }
        return $result;
    }

    public function getAllAttributes()
    {
        return $this->_attributeFactory->getCollection()->addFieldToFilter(\Magento\Eav\Model\Entity\Attribute\Set::KEY_ENTITY_TYPE_ID, 4);
    }
}
