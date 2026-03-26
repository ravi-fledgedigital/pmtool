<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\CatalogLabel\Model\Label\Rule\Condition;

use Magento\Rule\Model\Condition\Context;
use Magento\Framework\Model\AbstractModel;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;

class Combine extends \Magento\Rule\Model\Condition\Combine
{
    protected $labelRuleConditionProductFactory;

    /**
     * @var array
     */
    protected $_groups = [
        'base' => [
            'name',
            'attribute_set_id',
            'sku',
            'category_ids',
            'url_key',
            'visibility',
            'status',
            'default_category_id',
            'meta_description',
            'meta_keyword',
            'meta_title',
            'price',
            'special_price',
            'special_price_from_date',
            'special_price_to_date',
            'tax_class_id',
            'short_description',
            'full_description',
        ],
        'extra' => [
            'created_at',
            'updated_at',
            'qty',
            'final_price',
            'price_diff',
            'percent_discount',
            'set_as_new',
            'is_salable',
        ],
    ];

    public function __construct(
        ProductFactory $labelRuleConditionProductFactory,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setType('\\Mirasvit\\CatalogLabel\\Model\\Label\\Rule\\Condition\\Combine');

        $this->labelRuleConditionProductFactory = $labelRuleConditionProductFactory;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getNewChildSelectOptions(): array
    {
        $productCondition = $this->labelRuleConditionProductFactory->create();
        $productAttributes = $productCondition->loadAttributeOptions()->getAttributeOption();

        $attributes = [];
        foreach ($productAttributes as $code => $label) {
            $group = 'attributes';
            foreach ($this->_groups as $key => $values) {
                if (in_array($code, $values)) {
                    $group = $key;
                }
            }
            $attributes[$group][] = [
                'value' => '\\Mirasvit\\CatalogLabel\\Model\\Label\\Rule\\Condition\\Product|'.$code,
                'label' => $label,
            ];
        }

        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive($conditions, [
            [
                'value' => '\\Mirasvit\\CatalogLabel\\Model\\Label\\Rule\\Condition\\Combine',
                'label' => (string)__('Conditions Combination'),
            ],
            [
                'label' => (string)__('Product'),
                'value' => isset($attributes['base']) ? $attributes['base'] : [],
            ],
            [
                'label' => (string)__('Product Attribute'),
                'value' => isset($attributes['attributes']) ? $attributes['attributes'] : [],
            ],
            [
                'label' => (string)__('Product Additional'),
                'value' => isset($attributes['extra']) ? $attributes['extra'] : [],
            ],
        ]);

        return $conditions;
    }

    public function collectValidatedAttributes(ProductCollection $productCollection): self
    {
        foreach ($this->getConditions() as $condition) {
            $condition->collectValidatedAttributes($productCollection);
        }

        return $this;
    }
}
