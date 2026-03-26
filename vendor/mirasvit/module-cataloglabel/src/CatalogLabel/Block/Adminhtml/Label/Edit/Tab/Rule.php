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


namespace Mirasvit\CatalogLabel\Block\Adminhtml\Label\Edit\Tab;


use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Rule\Block\Conditions;
use Magento\Framework\Data\FormFactory;
use Magento\Backend\Model\Url;
use Magento\Framework\Registry;
use Magento\Backend\Block\Widget\Context;
use Mirasvit\CatalogLabel\Model\Indexer;


class Rule extends Form
{
    protected $widgetFormRendererFieldset;

    protected $conditions;

    protected $formFactory;

    protected $backendUrlManager;

    protected $registry;

    protected $context;

    protected $indexerRegistry;

    protected $_nameInLayout = 'cataloglabel_rule_tab';

    public function __construct(
        Fieldset $widgetFormRendererFieldset,
        Conditions $conditions,
        FormFactory $formFactory,
        Url $backendUrlManager,
        Registry $registry,
        IndexerRegistry $indexerRegistry,
        Context $context,
        array $data = []
    ) {
        $this->widgetFormRendererFieldset = $widgetFormRendererFieldset;
        $this->conditions                 = $conditions;
        $this->formFactory                = $formFactory;
        $this->backendUrlManager          = $backendUrlManager;
        $this->registry                   = $registry;
        $this->indexerRegistry            = $indexerRegistry;
        $this->context                    = $context;

        parent::__construct($context, $data);
    }

    public function getTabLabel(): string
    {
        return (string)__('Conditions');
    }

    public function getTabTitle(): string
    {
        return (string)__('Conditions');
    }

    public function canShowTab(): bool
    {
        return true;
    }

    public function isHidden(): bool
    {
        return false;
    }

    protected function _prepareForm(): Form
    {
        $formName = 'cataloglabel_label_form';
        $model    = $this->registry->registry('current_model');
        $form     = $this->formFactory->create();

        $form->setHtmlIdPrefix('rule_');

        $renderer = $this->widgetFormRendererFieldset
            ->setTemplate('Magento_CatalogRule::promo/fieldset.phtml')
            ->setNameInLayout('Mirasvit_CatalogLabel::label_conditions')
            ->setNewChildUrl($this->backendUrlManager
                ->getUrl('*/label/newConditionHtml/form/rule_conditions_fieldset'))
            ->setData('form_name', $formName);

        $fieldset = $form->addFieldset('conditions_fieldset', [
            'legend' => (string)__('Conditions (leave blank for all products)'), ]
        )->setRenderer($renderer);

        $rule = $model->getRule();

        $rule->getConditions()->setFormName($formName);

        $fieldset->addField('conditions', 'text', [
            'name'           => 'conditions',
            'label'          => (string)__('Conditions'),
            'title'          => (string)__('Conditions'),
            'required'       => true,
            'data-form-part' => $formName,
        ])->setRule($rule)->setRenderer($this->conditions)->setFormName($formName);

        $form->setValues($rule->getData());
        $this->setConditionFormName($rule->getConditions(), $formName);

        $info = $form->addFieldset('info_fieldset', []);

        $info->addField('count_proudcts', 'label', [
            'name'  => 'count_proudcts',
            'label' => (string)__('These conditions applied for %1 product(s)', count($model->getRule()->getProductIds())),
        ]);

        $idxr = $this->indexerRegistry->get(Indexer::INDEXER_ID);

        if ($idxr->isScheduled()) {
            $reindexNotice = $form->addFieldset('notice_fieldset', []);

            $reindexNotice->addField('reindex_notice', 'label', [
                'name'  => 'count_proudcts',
                'label' => (string)__(
                    'The "%1" index is set to "Update by Schedule". To apply label immediately press the "Apply Label" button after saving the label',
                    $idxr->getTitle()
                ),
            ]);
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }

    private function setConditionFormName($conditions, $formName)
    {
        $conditions->setFormName($formName);
        if ($conditions->getConditions() && is_array($conditions->getConditions())) {
            foreach ($conditions->getConditions() as $condition) {
                $this->setConditionFormName($condition, $formName);
            }
        }
    }
}
