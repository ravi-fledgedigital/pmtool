<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Block\Adminhtml\Tabs;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Amasty\CustomTabs\Api\TabsRepositoryInterface;
use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Ui\Component\Layout\Tabs\TabInterface;

class Conditions extends Generic implements TabInterface
{
    /**
     * @var Form\Renderer\Fieldset
     */
    private $rendererFieldset;

    /**
     * @var \Magento\Rule\Block\Conditions
     */
    private $conditions;

    /**
     * @var \Amasty\CustomTabs\Model\Tabs\RuleFactory
     */
    private $ruleFactory;

    /**
     * @var TabsRepositoryInterface
     */
    private $tabsRepository;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Amasty\CustomTabs\Model\Tabs\RuleFactory $ruleFactory,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Rule\Block\Conditions $conditions,
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset,
        TabsRepositoryInterface $tabsRepository,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->conditions = $conditions;
        $this->rendererFieldset = $rendererFieldset;
        $this->ruleFactory = $ruleFactory;
        $this->tabsRepository = $tabsRepository;
    }

    /**
     * @inheritdoc
     */
    public function getTabClass()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getTabUrl()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getTabLabel()
    {
        return __('Conditions');
    }

    /**
     * @inheritdoc
     */
    public function getTabTitle()
    {
        return __('Conditions');
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    protected function _prepareForm()
    {
        $model = $this->ruleFactory->create();
        $tabId = $this->_request->getParam(TabsInterface::TAB_ID, null);
        if ($tabId !== null) {
            $tab = $this->tabsRepository->getById((int)$tabId);
            $model->setConditionsSerialized($tab->getConditionsSerialized());
        }
        $form = $this->addTabToForm($model);
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * @param \Magento\CatalogRule\Model\Rule $model
     * @param string $fieldsetId
     * @param string $formName
     * @return \Magento\Framework\Data\Form
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function addTabToForm(
        \Magento\CatalogRule\Model\Rule $model,
        $fieldsetId = 'conditions_fieldset',
        $formName = 'amcustomtabs_tabs_form_data_source'
    ) {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_');
        $conditionsFieldSetId = $model->getConditionsFieldSetId($formName);
        $newChildUrl = $this->getUrl(
            'catalog_rule/promo_catalog/newConditionHtml/form/' . $conditionsFieldSetId,
            ['form_namespace' => $formName]
        );

        $renderer = $this->rendererFieldset
            ->setTemplate('Magento_CatalogRule::promo/fieldset.phtml')
            ->setNewChildUrl($newChildUrl)
            ->setFieldSetId($conditionsFieldSetId)
            ->setNameInLayout('amblog.renderer.fieldset');

        $fieldset = $form->addFieldset(
            $fieldsetId,
            ['legend' => __('Apply the rule only if the following conditions are met (leave blank for all products).')]
        )
            ->setRenderer($renderer);

        $fieldset->addField(
            'conditions',
            'text',
            [
                'name' => 'conditions',
                'label' => __('Conditions'),
                'title' => __('Conditions'),
                'required' => true
            ]
        )->setRule($model)->setRenderer($this->conditions);

        $form->setValues($model->getData());
        $this->setConditionFormName($model->getConditions(), $formName, $conditionsFieldSetId);

        return $form;
    }

    /**
     * @param \Magento\Rule\Model\Condition\AbstractCondition $conditions
     * @param string $formName
     * @param string $jsFormName
     * @return void
     */
    private function setConditionFormName(
        \Magento\Rule\Model\Condition\AbstractCondition $conditions,
        $formName,
        $jsFormName
    ) {
        $conditions->setFormName($formName);
        $conditions->setJsFormObject($jsFormName);

        if ($conditions->getConditions() && is_array($conditions->getConditions())) {
            foreach ($conditions->getConditions() as $condition) {
                $this->setConditionFormName($condition, $formName, $jsFormName);
            }
        }
    }

    public function getNameInLayout(): string
    {
        $nameInLayout = parent::getNameInLayout();
        if ($nameInLayout === null) {
            $this->setNameInLayout('amtabs.conditions');
        }

        return parent::getNameInLayout();
    }
}
