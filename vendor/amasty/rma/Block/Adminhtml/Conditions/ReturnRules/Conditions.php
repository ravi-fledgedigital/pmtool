<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Adminhtml\Conditions\ReturnRules;

use Amasty\Rma\Model\ReturnRules\ReturnRules;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Rule\Model\Condition\AbstractCondition;

class Conditions extends Generic
{
    /**
     * Block name in layout
     *
     * @var string
     */
    protected $_nameInLayout = 'conditions';

    /**
     * @var ReturnRules
     */
    private $rule;

    /**
     * @var \Magento\Rule\Block\Conditions
     */
    private $conditions;

    /**
     * @var \Magento\Framework\Data\FormFactory
     */
    private $formFactory;

    /**
     * @var \Magento\Backend\Block\Widget\Form\Renderer\Fieldset
     */
    private $fieldset;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    private $metadata;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Rule\Block\Conditions $conditions,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $fieldset,
        \Magento\Framework\App\ProductMetadataInterface $metadata,
        array $data = []
    ) {
        $this->rule = $registry->registry(ReturnRules::CURRENT_RETURN_RULE);
        parent::__construct($context, $registry, $formFactory, $data);
        $this->conditions = $conditions;
        $this->formFactory = $formFactory;
        $this->fieldset = $fieldset;
        $this->metadata = $metadata;
    }

    /**
     * @inheritdoc
     */
    public function toHtml()
    {
        if (version_compare($this->metadata->getVersion(), '2.2.0', '>=')) {
            //Fix for Magento >2.2.0 to display right form layout.
            //Result of compatibility with 2.1.x.
            $this->_prepareLayout();
        }
        $conditionsFieldSetId = ReturnRules::FORM_NAMESPACE
            . 'rule_conditions_fieldset';
        $newChildUrl = $this->getUrl(
            'amrma/returnrules/newConditionHtml/form/' . $conditionsFieldSetId,
            ['form_namespace' => ReturnRules::FORM_NAMESPACE]
        );

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->formFactory->create();
        $renderer = $this->fieldset->setTemplate('Magento_CatalogRule::promo/fieldset.phtml')
            ->setNewChildUrl($newChildUrl)
            ->setFieldSetId($conditionsFieldSetId)
            ->setNameInLayout('amrma_conditions');
        $fieldset = $form->addFieldset(
            $conditionsFieldSetId,
            []
        )->setRenderer(
            $renderer
        );
        $fieldset->addField(
            'conditions' . $conditionsFieldSetId,
            'text',
            [
                'name' => 'conditions' . $conditionsFieldSetId,
                'label' => __('Conditions'),
                'title' => __('Conditions'),
                'required' => true,
                'data-form-part' => ReturnRules::FORM_NAMESPACE,
            ]
        )->setRule($this->rule)->setRenderer($this->conditions);
        $form->setValues($this->rule->getData());
        $this->setConditionFormName($this->rule->getConditions(), ReturnRules::FORM_NAMESPACE);

        return $form->toHtml();
    }

    /**
     * @param AbstractCondition $abstractConditions
     * @param string $formName
     *
     * @return void
     */
    private function setConditionFormName(AbstractCondition $abstractConditions, $formName)
    {
        $conditionsFieldSetId = ReturnRules::FORM_NAMESPACE
            . 'rule_conditions_fieldset';
        $abstractConditions->setFormName($formName);
        $abstractConditions->setJsFormObject($conditionsFieldSetId);
        $conditions = $abstractConditions->getConditions();

        if ($conditions && is_array($conditions)) {
            foreach ($conditions as $condition) {
                $this->setConditionFormName($condition, $formName);
                $condition->setJsFormObject($conditionsFieldSetId);
            }
        }
    }
}
