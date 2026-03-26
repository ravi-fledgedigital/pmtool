<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Attribute;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Escaper;

class Attribute extends ItemsProvider
{
    /**
     * @var Escaper
     */
    private $escaper;

    public function __construct(CollectionFactory $collectionFactory, Escaper $escaper)
    {
        parent::__construct($collectionFactory);
        $this->escaper = $escaper;
    }

    /**
     * @return array
     */
    public function getVariablesOptionArray()
    {
        $collection = $this->getAttributeItems();
        $variables = [];
        foreach ($collection as $attribute) {
            $variables[] = [
                'value' => '{{amcustomtabs_attribute code="' . $attribute->getAttributeCode() . '"}}',
                'label' => __('%1', $this->escaper->escapeHtml($attribute->getFrontendLabel())),
            ];
        }
        if ($variables) {
            $variables = ['label' => __('Custom Variables'), 'value' => $variables];
        }
        return $variables;
    }
}
