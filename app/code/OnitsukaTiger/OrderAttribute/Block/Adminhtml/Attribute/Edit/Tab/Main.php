<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Block\Adminhtml\Attribute\Edit\Tab;

use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface;
use OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\InputTypeProvider;
use Magento\Backend\Block\Widget\Form\Element\Dependence;
use Magento\Eav\Block\Adminhtml\Attribute\Edit\Main\AbstractMain;

class Main extends AbstractMain
{
    private const INPUT_TYPE_MULTISELECT = 'multiselect';

    /**
     * @var array
     */
    protected $fieldToRemoveFromFieldset = [
        'is_unique',
        'frontend_class'
    ];

    /**
     * @var Dependence|null
     */
    protected $dependencies = null;

    /**
     * @var \Magento\Store\Model\System\Store
     */
    private $systemStore;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\ConfigProvider
     */
    private $configProvider;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Config\Source\CustomerGroup
     */
    private $customerGroupSource;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Config\Source\CheckoutStep
     */
    private $checkoutStepSource;

    /**
     * @var \Magento\Config\Model\Config\Structure\Element\Dependency\FieldFactory
     */
    private $dependencyFieldFactory;

    /**
     * @var InputTypeProvider
     */
    private $inputTypeProvider;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Config\Source\Boolean
     */
    private $boolean;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Eav\Helper\Data $eavData,
        \Magento\Config\Model\Config\Source\YesnoFactory $yesnoFactory,
        \Magento\Eav\Model\Adminhtml\System\Config\Source\InputtypeFactory $inputTypeFactory,
        \Magento\Eav\Block\Adminhtml\Attribute\PropertyLocker $propertyLocker,
        \Magento\Store\Model\System\Store $systemStore,
        \OnitsukaTiger\OrderAttribute\Model\ConfigProvider $configProvider,
        \Magento\Config\Model\Config\Structure\Element\Dependency\FieldFactory $dependencyFieldFactory,
        \OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\InputTypeProvider $inputTypeProvider,
        \OnitsukaTiger\OrderAttribute\Model\Config\Source\CustomerGroup $customerGroupSource,
        \OnitsukaTiger\OrderAttribute\Model\Config\Source\CheckoutStep $checkoutStepSource,
        \OnitsukaTiger\OrderAttribute\Model\Config\Source\Boolean $boolean,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $eavData,
            $yesnoFactory,
            $inputTypeFactory,
            $propertyLocker,
            $data
        );
        $this->systemStore = $systemStore;
        $this->dependencyFieldFactory = $dependencyFieldFactory;
        $this->configProvider = $configProvider;
        $this->customerGroupSource = $customerGroupSource;
        $this->checkoutStepSource = $checkoutStepSource;
        $this->inputTypeProvider = $inputTypeProvider;
        $this->boolean = $boolean;
    }

    protected function _prepareForm()
    {
        parent::_prepareForm();
        /** @var \OnitsukaTiger\OrderAttribute\Model\Attribute\Attribute $attributeObject */
        $attributeObject = $this->getAttributeObject();
        $yesno = $this->_yesnoFactory->create()->toOptionArray();

        /** @var $form \Magento\Framework\Data\Form */
        $form = $this->getForm();
        /** @var $fieldset \Magento\Framework\Data\Form\Element\Fieldset */
        $fieldset = $form->getElement('base_fieldset');
        $fieldset->setLegend(__('General Configuration'));

        /** Yes/No default values with empty value */
        $fieldset->getElements()
            ->searchById('default_value_yesno')
            ->setValues($this->boolean->toOptionArray());

        $this->removeFieldsFromAbstract($fieldset);

        if (!$this->_storeManager->isSingleStoreMode()) {
            $storeValues = $this->systemStore->getStoreValuesForForm();
            $fieldset->addField(
                'store_ids',
                'multiselect',
                [
                    'name'     => 'store_ids[]',
                    'label'    => __('Store View'),
                    'title'    => __('Store View'),
                    'required' => true,
                    'values'   => $storeValues,
                ],
                'attribute_code'
            );
        } else {
            $fieldset->addField(
                'store_ids',
                'hidden',
                [
                    'name'  => 'store_ids[]',
                    'value' => $this->_storeManager->getStore()->getId()
                ],
                'attribute_code'
            );
            $attributeObject->setStoreIds($this->_storeManager->getStore(true)->getId());
        }

        $fieldset->addField(
            'default_value_html',
            'textarea',
            [
                'name' => 'default_value_html',
                'label' => __('Default Value'),
                'title' => __('Default Value'),
                'value' => $attributeObject->getDefaultValue()
            ],
            'default_value_textarea'
        );

        $groupValues = $this->customerGroupSource->toOptionArray();
        $preselectedGroupValues = array_column($groupValues, 'value');
        $fieldset->addField(
            'customer_groups',
            'multiselect',
            [
                'name'   => 'customer_groups[]',
                'label'  => ('Customer Groups'),
                'title'  => ('Customer Groups'),
                'values' => $groupValues,
                'value' => $preselectedGroupValues
            ],
            'store_ids'
        );

        $fieldset = $form->addFieldset(
            'attribute_type_settings',
            ['legend' => __('Attribute Type Settings')]
        );

        /** @var \Magento\Framework\Data\Form\Element\Select $frontendInputElm */
        $frontendInputElm = $form->getElement('frontend_input');
        $frontendInputElm->setLabel(__('Frontend Input Type'));
        $frontendInputElm->setValues($this->inputTypeProvider->toOptionArray());

        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $timeFormat = $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT);
        $fieldset->addField(
            'default_value_datetime',
            'date',
            [
                'name' => 'default_value_datetime',
                'label' => __('Default Value'),
                'title' => __('Default Value'),
                'value' => $attributeObject->getDefaultValue(),
                'date_format' => $dateFormat,
                'time_format' => $timeFormat
            ],
            'default_value_date'
        );

        $fieldset->addField(
            'date_range_min',
            'date',
            [
                'name' => 'date_range_min',
                'label' => __('Minimal value'),
                'title' => __('Minimal value'),
                'date_format' => $dateFormat
            ],
            'default_value_datetime'
        );

        $fieldset->addField(
            'date_range_max',
            'date',
            [
                'name' => 'date_range_max',
                'label' => __('Maximum value'),
                'title' => __('Maximum value'),
                'date_format' => $dateFormat
            ],
            'date_range_min'
        );

        $multi = $fieldset->addField(
            CheckoutAttributeInterface::MULTISELECT_SIZE,
            'text',
            [
                'name' => CheckoutAttributeInterface::MULTISELECT_SIZE,
                'label' => __('Multiple Select Size'),
                'title' => __('Multiple Select Size'),
                'value' => $attributeObject->getMultiselectSize(),
            ],
            'default_value_textarea'
        );

        $visibleOnFrontField = $fieldset->addField(
            CheckoutAttributeInterface::IS_VISIBLE_ON_FRONT,
            'select',
            [
                'name'   => CheckoutAttributeInterface::IS_VISIBLE_ON_FRONT,
                'label'  => __('Visible on Front-end'),
                'title'  => __('Visible on Front-end'),
                'values' => $yesno,
            ],
            CheckoutAttributeInterface::MULTISELECT_SIZE
        );

        $hiddenFromCustomerField = $fieldset->addField(
            CheckoutAttributeInterface::IS_HIDDEN_FROM_CUSTOMER,
            'select',
            [
                'name'      => CheckoutAttributeInterface::IS_HIDDEN_FROM_CUSTOMER,
                'label'     => __('Hidden from Customer'),
                'title'     => __('Hidden from Customer'),
                'values'    => $yesno,
                'note'      => __(
                    'Once enabled, the attribute won’t show on the storefront. However, it will be stored'
                    . ' in the order with a default value. Make sure to specify the default value.'
                )
            ],
            CheckoutAttributeInterface::IS_VISIBLE_ON_FRONT
        );

        $this->makeDependence(
            $visibleOnFrontField,
            $hiddenFromCustomerField,
            $this->dependencyFieldFactory->create([
                'fieldData' => ['value' => '1']
            ])
        );

        $fieldset->addField(
            CheckoutAttributeInterface::IS_VISIBLE_ON_BACK,
            'select',
            [
                'name'   => CheckoutAttributeInterface::IS_VISIBLE_ON_BACK,
                'label'  => __('Visible on Back-end'),
                'title'  => __('Visible on Back-end'),
                'values' => $yesno,
            ],
            CheckoutAttributeInterface::IS_HIDDEN_FROM_CUSTOMER
        );

        $requiredElm = $form->getElement('is_required');
        $requiredValues = array_merge(
            $requiredElm->getValues(),
            [
                [
                    'value' => CheckoutAttributeInterface::IS_REQUIRED_PROXY_VALUE,
                    'label' => __('On the Frontend Only')
                ]
            ]
        );
        $requiredElm->setValues($requiredValues);

        $fieldset->addField(
            'input_validation',
            'select',
            [
                'name'   => 'input_validation',
                'label'  => __('Input Validation'),
                'title'  => __('Input Validation'),
                'note'   => __('Enable This Option to Check the filled in data according to the defined rule'),
                'values' => $this->inputTypeProvider->getInputValidationOptionArray()
            ],
            'default_value_textarea'
        );

        $fieldset->addField(
            'min_text_length',
            'text',
            [
                'name' => 'min_text_length',
                'label' => __('Minimum Text Length'),
                'title' => __('Minimum Text Length'),
                'class' => 'validate-digits'
            ],
            'input_validation'
        );

        $fieldset->addField(
            'max_text_length',
            'text',
            [
                'name' => 'max_text_length',
                'label' => __('Maximum Text Length'),
                'title' => __('Maximum Text Length'),
                'class' => 'validate-digits'
            ],
            'min_text_length'
        );

        $fieldset->addField(
            'input_filter',
            'select',
            [
                'name' => 'input_filter',
                'label' => __('Input/Output Filter'),
                'title' => __('Input/Output Filter'),
                'values' => $this->inputTypeProvider->getInputFilterOptionArray()
            ]
        );

        $fieldset->addField(
            'max_file_size',
            'text',
            [
                'name'  => 'max_file_size',
                'label' => __('Max File Size'),
                'title' => __('Max File Size - in Mb'),
                'note'  => __('In Mb'),
            ],
            'input_validation'
        );

        $fieldset->addField(
            'file_extensions',
            'text',
            [
                'name'  => 'file_extensions',
                'label' => __('File Types'),
                'title' => __('File Types - list comma-separated file types with no spaces, like: png,txt,jpg'),
                'note'  => __('List comma-separated file types with no spaces, like: png,txt,jpg'),
            ],
            'max_file_size'
        );

        $this->makeDependence(
            $frontendInputElm,
            $multi,
            $this->dependencyFieldFactory->create([
                'fieldData' => ['value' => self::INPUT_TYPE_MULTISELECT]
            ])
        );

        $fieldset = $form->addFieldset(
            'front_fieldset',
            ['legend' => __('Attribute Display Settings')]
        );

        $fieldset->addField(
            CheckoutAttributeInterface::CHECKOUT_STEP,
            'select',
            [
                'name'   => CheckoutAttributeInterface::CHECKOUT_STEP,
                'label'  => __('Position at Checkout Step'),
                'title'  => __('Position at Checkout Step'),
                'values' => $this->checkoutStepSource->toOptionArray(),
            ]
        );

        $fieldset->addField(
            CheckoutAttributeInterface::SORTING_ORDER,
            'text',
            [
                'name'  => CheckoutAttributeInterface::SORTING_ORDER,
                'label' => __('Sort Order'),
                'title' => __('Sort Order'),
                'note'  => __('Numeric, used in front-end to sort attributes'),
            ]
        );

        $fieldset->addField(
            CheckoutAttributeInterface::SAVE_TO_FUTURE_CHECKOUT,
            'select',
            [
                'name'   => CheckoutAttributeInterface::SAVE_TO_FUTURE_CHECKOUT,
                'label'  => __('Save Entered Value For Future Checkout'),
                'title'  => __('Save Entered Value For Future Checkout'),
                'note'   => __(
                    'If set to "Yes", previously entered value will be used during checkout.' .
                    ' Works for registered customers only.'
                ),
                'values' => $yesno,
            ]
        );

        $fieldset->addField(
            CheckoutAttributeInterface::SHOW_ON_GRIDS,
            'select',
            [
                'name'   => CheckoutAttributeInterface::SHOW_ON_GRIDS,
                'label'  => __('Show on Admin Grids'),
                'title'  => __('Show on Admin Grids'),
                'values' => $yesno,
            ]
        );

        $fieldset->addField(
            CheckoutAttributeInterface::INCLUDE_IN_HTML_PRINT_ORDER,
            'select',
            [
                'name'   => CheckoutAttributeInterface::INCLUDE_IN_HTML_PRINT_ORDER,
                'label'  => __('Include Into HTML Print-out'),
                'title'  => __('Include Into HTML Print-out'),
                'note'   => __('Order attributes will be included in the document that a customer prints
                    in his account'),
                'values' => $yesno,
            ]
        );

        $fieldset->addField(
            CheckoutAttributeInterface::INCLUDE_IN_PDF,
            'select',
            [
                'name'   => CheckoutAttributeInterface::INCLUDE_IN_PDF,
                'label'  => __('Include Into PDF Documents'),
                'title'  => __('Include Into PDF Documents'),
                'note'   => __('Please, make sure that the \'attributes display in PDF files\' option is enabled in
                    the extension\'s general settings before modifying this option.'),
                'values' => $yesno,
            ]
        );

        $fieldset->addField(
            CheckoutAttributeInterface::INCLUDE_IN_EMAIL,
            'select',
            [
                'name'   => CheckoutAttributeInterface::INCLUDE_IN_EMAIL,
                'label'  => __('Include Into Transactional Emails'),
                'title'  => __('Include Into Transactional Emails'),
                'note'   => __('Please, make sure that the \'Include Order Attributes into Emails\' option is enabled
                 in the extension\'s general settings before modifying this option.'),
                'values' => $yesno,
            ]
        );

        $fieldset->addField(
            CheckoutAttributeInterface::APPLY_DEFAULT_VALUE,
            'hidden',
            [
                'name'   => CheckoutAttributeInterface::APPLY_DEFAULT_VALUE,
                'label'  => __('Automatically Apply Default Value'),
                'title'  => __('Automatically Apply Default Value'),
                'note'   => __(
                    'If set to `Yes`, the default value will be automatically applied for each order' .
                    ' if attribute value is not entered or not visible at the frontend.'
                ),
                'values' => $yesno,
            ]
        );

        $this->setChild('form_after', $this->dependencies);
        $this->setForm($form);

        return $this;
    }

    /**
     * Initialize form fileds values
     *
     * @return \Magento\Eav\Block\Adminhtml\Attribute\Edit\Main\AbstractMain
     */
    protected function _initFormValues()
    {
        $attribute = $this->getAttributeObject();
        if ($attribute->getId() && $attribute->getValidateRules()) {
            $this->getForm()->addValues($attribute->getValidateRules());
        }
        $data = $attribute->getData();
        if (!$this->_storeManager->isSingleStoreMode()) {
            if (!$attribute->getAvailableInStores()) {
                $storecollection = $this->systemStore->getStoreCollection();
                $stores = [];
                /** @var \Magento\Store\Model\Store $store */
                foreach ($storecollection as $store) {
                    $stores[] = $store->getId();
                }
                $data['store_ids'] = $stores;
            } else {
                $data['store_ids'] = $attribute->getAvailableInStores();
            }
        }
        if ($attribute->getId()) {
            if ($attribute->getCustomerGroups()) {
                $data['customer_groups'] = $attribute->getCustomerGroups();
            }

            if (array_key_exists(CheckoutAttributeInterface::REQUIRED_ON_FRONT_ONLY, $data)
                && $data[CheckoutAttributeInterface::REQUIRED_ON_FRONT_ONLY] == 1
            ) {
                $data[CheckoutAttributeInterface::IS_REQUIRED] = CheckoutAttributeInterface::IS_REQUIRED_PROXY_VALUE;
            }
        }

        $defaultYesArray = [
            CheckoutAttributeInterface::IS_VISIBLE_ON_FRONT,
            CheckoutAttributeInterface::IS_VISIBLE_ON_BACK,
            CheckoutAttributeInterface::INCLUDE_IN_EMAIL
        ];
        foreach ($defaultYesArray as $key) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = 1;
            }
        }

        $attribute->setData($data);

        parent::_initFormValues();

        return $this;
    }

    protected function makeDependence($mainElement, $dependentElement, $values = '1')
    {
        if (!$this->dependencies) {
            $this->dependencies = $this->getLayout()
                ->createBlock(Dependence::class);
        }

        $this->dependencies->addFieldMap($mainElement->getHtmlId(), $mainElement->getName())
            ->addFieldMap($dependentElement->getHtmlId(), $dependentElement->getName())
            ->addFieldDependence($dependentElement->getName(), $mainElement->getName(), $values);
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\Fieldset $fieldset
     */
    protected function removeFieldsFromAbstract($fieldset)
    {
        foreach ($this->fieldToRemoveFromFieldset as $fieldCode) {
            $fieldset->removeField($fieldCode);
        }
    }
}
