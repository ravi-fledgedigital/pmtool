<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product;

use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Attribute as AttributeCondition;
use Magento\AdvancedRule\Model\Condition\FilterTextGeneratorInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Locale\FormatInterface;

class Attribute implements FilterTextGeneratorInterface
{
    /**
     * @var string
     */
    protected $attributeCode;

    /**
     * @var FormatInterface
     */
    private FormatInterface $localeFormat;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @param array $data
     * @param Config $config
     * @param FormatInterface $localeFormat
     */
    public function __construct(
        array $data,
        Config $config,
        FormatInterface $localeFormat
    ) {
        $this->attributeCode = $data['attribute'];
        $this->config = $config;
        $this->localeFormat = $localeFormat;
    }

    /**
     * @inheritdoc
     */
    public function generateFilterText(\Magento\Framework\DataObject $quoteAddress)
    {
        $filterText = [];
        if ($quoteAddress instanceof \Magento\Quote\Model\Quote\Address) {
            $items = $quoteAddress->getAllItems();
            $attribute = $this->getAttributeObject();
            foreach ($items as $item) {
                $product = $item->getProduct();
                $value = $product->getData($this->attributeCode);
                if ($attribute && $attribute->getBackendType() === 'decimal') {
                    $value = $this->localeFormat->getNumber($value);
                }
                if (is_scalar($value)) {
                    $text = AttributeCondition::FILTER_TEXT_PREFIX . $this->attributeCode . ':' . $value;
                    if (!in_array($text, $filterText)) {
                        $filterText[] = $text;
                    }
                }
            }
        }
        return $filterText;
    }

    /**
     * Retrieve attribute object
     *
     * @return ?AbstractAttribute
     */
    private function getAttributeObject(): ?AbstractAttribute
    {
        try {
            $attributeObject = $this->config->getAttribute(Product::ENTITY, $this->attributeCode);
        } catch (\Exception $e) {
            $attributeObject = null;
        }

        return $attributeObject;
    }
}
