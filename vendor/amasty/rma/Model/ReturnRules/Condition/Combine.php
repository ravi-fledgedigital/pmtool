<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\ReturnRules\Condition;

use Magento\CatalogRule\Model\Rule\Condition\Combine as CatalogRuleCombine;

class Combine extends CatalogRuleCombine
{
    /**
     * @var SaleFactory
     */
    private $saleConditionFactory;

    public function __construct(
        SaleFactory $saleConditionFactory,
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CatalogRule\Model\Rule\Condition\ProductFactory $conditionFactory,
        array $data = []
    ) {
        parent::__construct($context, $conditionFactory, $data);
        $this->setType(\Amasty\Rma\Model\ReturnRules\Condition\Combine::class);
        $this->saleConditionFactory = $saleConditionFactory;
    }

    public function getNewChildSelectOptions()
    {
        $conditions = parent::getNewChildSelectOptions();

        /** @var Sale $saleCondition */
        $saleCondition = $this->saleConditionFactory->create();
        $saleConditionAttributes = $saleCondition->loadAttributeOptions()->getAttributeOption();
        $saleAttributes = [];

        foreach ($saleConditionAttributes as $code => $label) {
            $saleAttributes[] = [
                'value' => 'Amasty\Rma\Model\ReturnRules\Condition\Sale' . '|' . $code,
                'label' => $label
            ];
        }

        $conditions[] = [
            'value' => $saleAttributes,
            'label' => __('Sale attributes')
        ];

        return $conditions;
    }
}
