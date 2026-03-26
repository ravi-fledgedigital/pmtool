<?php

namespace OnitsukaTigerIndo\RmaAccount\Block\Adminhtml\Items\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Main extends Generic implements TabInterface
{
    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfig;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry             $registry,
        \Magento\Framework\Data\FormFactory     $formFactory,
        \Magento\Cms\Model\Wysiwyg\Config       $wysiwygConfig,
        array                                   $data = []
    ) {
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @inheritdoc
     */
    public function getTabLabel()
    {
        return __('RMA Accounts Information');
    }

    /**
     * @inheritdoc
     */
    public function getTabTitle()
    {
        return __('Account Information');
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
     * Prepare form before rendering HTML
     *
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_onitsukatigerindo_rmaaccount_items');
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('item_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Account Details')]);
        if ($model->getId()) {
            $fieldset->addField('rma_id', 'hidden', ['name' => 'rma_id']);
        }
        $fieldset->addField(
            'order_number',
            'text',
            ['name' => 'order_number', 'label' => __('Order number'), 'title' => __('Order Number'), 'required' => true]
        );
        $fieldset->addField(
            'acc_holder_name',
            'text',
            ['name' => 'acc_holder_name', 'label' => __('Account Holder Name'),
                'title' => __('Account Holder Name'), 'required' => true]
        );
        $fieldset->addField(
            'bank_name',
            'text',
            ['name' => 'bank_name', 'label' => __('Bank Name'), 'title' => __('Bank Name'),
                'required' => true]
        );
        $fieldset->addField(
            'account_number',
            'text',
            ['name' => 'account_number', 'label' => __('Account Number'),
                'title' => __('Account Number'), 'required' => true]
        );
        $fieldset->addField(
            'ifsc_code',
            'text',
            ['name' => 'ifsc_code', 'label' => __('IFSC'), 'title' => __('IFSC'), 'required' => true]
        );

        $form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
