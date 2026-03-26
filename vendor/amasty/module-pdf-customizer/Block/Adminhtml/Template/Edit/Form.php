<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Block\Adminhtml\Template\Edit;

use Magento\Framework\Data\Form\Element\Fieldset;

class Form extends \Magento\Email\Block\Adminhtml\Template\Edit\Form
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    private $systemStoreSource;

    /**
     * @var \Amasty\PDFCustom\Model\Source\PlaceForUse
     */
    private $placeForUseSource;

    /**
     * @var \Amasty\PDFCustom\Model\Source\CustomerGroup
     */
    private $customerGroupSource;

    /**
     * trick for not overload __construct because of changed params in magento 2.3
     * @return void
     */
    protected function _construct()
    {
        $this->systemStoreSource = $this->getData('systemStoreSource');
        $this->placeForUseSource = $this->getData('placeForUseSource');
        $this->customerGroupSource = $this->getData('customerGroupSource');
        parent::_construct();
    }

    /**
     * Add fields to form and create template info form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        parent::_prepareForm();
        $this->getForm()->getElement('base_fieldset')->removeField('template_subject');
        $this->getForm()->getElement('base_fieldset')->removeField('currently_used_for');

        $this->addAdditionalFields();
        return $this;
    }

    /**
     * Return current PDF template model
     *
     * @return \Magento\Email\Model\Template
     */
    public function getEmailTemplate()
    {
        return $this->getData('email_template');
    }

    /**
     * @return array
     */
    public function getVariables()
    {
        $variables = $this->_variables->toOptionArray(true);
        if (isset($variables['label'])) {
            $variables = [$variables];
        }
        $customVariables = $this->_variableFactory->create()->getVariablesOptionArray(true);
        if ($customVariables) {
            if (isset($customVariables['label'])) {
                $customVariables = [$customVariables];
            }
            $variables = array_merge_recursive($variables, $customVariables);
        }
        $template = $this->getEmailTemplate();
        if ($template->getId() && ($templateVariables = $template->getVariablesOptionArray(true))) {
            if (isset($templateVariables['label'])) {
                $templateVariables = [$templateVariables];
            }
            $variables = array_merge_recursive($variables, $templateVariables);
        }
        return $variables;
    }

    /**
     * @return void
     */
    protected function addAdditionalFields()
    {
        $form = $this->getForm();
        /** @var Fieldset $fieldset */
        $fieldset = $form->getElement('base_fieldset');

        $fieldset->addField(
            'place_for_use',
            'select',
            [
                'name' => 'place_for_use',
                'label' => __('Use the Template For'),
                'title' => __('Use the Template For'),
                'required' => true,
                'values' => $this->placeForUseSource->toOptionArray(),
            ],
            '^'
        );

        $groupsFieldset = $form->addFieldset('customer_group', ['legend' => __('Stores & Customer Groups')]);
        $groupsFieldset->addField(
            'store_ids',
            'multiselect',
            [
                'name' => 'store_ids[]',
                'label' => __('Use for Stores'),
                'title' => __('Use for Stores'),
                'required' => false,
                'values' => $this->systemStoreSource->getStoreValuesForForm(false, true),
                'note' => __('Please specify Storeviews for which the template will be used.' .
                    ' Leave empty or select all to use the template for any storeview.')
            ],
            'template_styles'
        );

        $groupsFieldset->addField(
            'customer_group_ids',
            'multiselect',
            [
                'name' => 'customer_group_ids[]',
                'label' => __('Use for Customer Groups'),
                'title' => __('Use for Customer Groups'),
                'required' => false,
                'values' => $this->customerGroupSource->toOptionArray(),
                'note' => __('Please specify customer groups for which the template will be used. Leave empty or ' .
                    'select all to use the template for any customer group. Additive with Storeviews choice.')
            ],
            'store_ids'
        );

        $emailTemplate = $this->getEmailTemplate();
        $form->addValues(
            [
                'place_for_use' => $emailTemplate->getPlaceForUse(),
                'store_ids' => $emailTemplate->getStoreIds(),
                'customer_group_ids' => $emailTemplate->getCustomerGroupIds(),
            ]
        );
    }
}
