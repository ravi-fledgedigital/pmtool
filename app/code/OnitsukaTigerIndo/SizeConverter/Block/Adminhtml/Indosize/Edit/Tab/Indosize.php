<?php

namespace OnitsukaTigerIndo\SizeConverter\Block\Adminhtml\Indosize\Edit\Tab;

class Indosize extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    /**
     * @var \OnitsukaTigerIndo\SizeConverter\Model\Source\EnglishSizeOptions
     */
    protected $sizeOptions;

    /**
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \OnitsukaTigerIndo\SizeConverter\Model\Source\EnglishSizeOptions $sizeOptions,
        private \Magento\Eav\Model\Config $eavConfig,
        private \OnitsukaTigerIndo\SizeConverter\Model\IndoSizeFactory $indoSizeFactory,
        private \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->sizeOptions = $sizeOptions;
        $this->systemStore = $systemStore;
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('indo_size');

        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Size Information')]);

        if ($model->getId()) {
            $fieldset->addField('size_id', 'hidden', ['name' => 'size_id']);
        }

        $fieldset->addField(
            'english_size',
            'select',
            [
                'label' => __('English Size'),
                'title' => __('English Size'),
                'name' => 'english_size',
                'type' => 'options',
                'required' => true,
                'values' => $this->getSizeOptions(),
            ]
        );

        $fieldset->addField(
            'gender',
            'select',
            [
                'label' => __('Gender'),
                'title' => __('Gender'),
                'name' => 'gender',
                'type' => 'options',
                'required' => true,
                'values' => $this->getGenderOptions(),
            ]
        );

        $fieldset->addField(
            'euro_size',
            'text',
            [
                'name' => 'euro_size',
                'label' => __('Euro Size'),
                'title' => __('Euro Size'),
                'required' => true
            ]
        );
        $fieldset->addField(
            'store_ids',
            'multiselect',
            [
                'name'     => 'store_ids[]',
                'label'    => __('Store View'),
                'title'    => __('Store View'),
                'required' => true,
                'values'   => $this->systemStore->getStoreValuesForForm(false, true),
            ]
        );

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Size Information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Size Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Get size options for dropdown.
     *
     * @return array
     */
    public function getSizeOptions()
    {
        $attribute = $this->eavConfig->getAttribute('catalog_product', 'qa_size');
        $options = [];
        foreach ($attribute->getSource()->getAllOptions() as $option) {
            $options[$option['value']] = $option['label'];
        }
        return $options;
    }

    /**
     * Get gender options for dropdown.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getGenderOptions()
    {
        $attribute = $this->eavConfig->getAttribute('catalog_product', 'gender');
        $options = [];
        foreach ($attribute->getSource()->getAllOptions() as $option) {
            $options[$option['value']] = $option['label'];
        }
        return $options;
    }

}
