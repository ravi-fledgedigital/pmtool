<?php

namespace OnitsukaTiger\SizeGuide\Block\Adminhtml\System\Config\Form\Field\Renderer;

use Magento\Framework\View\Element\Html\Select;

class ProductGroup extends Select
{
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeResource,
        array $data = []
    ) {
        $this->attributeResource = $attributeResource;
        parent::__construct($context, $data);
    }

    public function setInputName($value)
    {
        return $this->setName($value . '[]');
    }

    protected function _toHtml()
    {
        if (!$this->getOptions()) {
            $attribute = $this->attributeResource->loadByCode('catalog_product', 'product_group');
            foreach ($attribute->getSource()->getAllOptions(false) as $option) {
                $zname_clean = preg_replace('/\s*/', '', $option['label']);
                $zname_clean = strtolower($zname_clean);
                $this->addOption($zname_clean, $option['label']);
            }
        }
        $this->setExtraParams('multiple="multiple"');
        return parent::_toHtml();
    }
}
