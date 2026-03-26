<?php
namespace Cpss\Crm\Block\Adminhtml\RealStore\Edit;

use Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration\GetData;

class Form extends \Magento\Backend\Block\Widget\Form\Generic {
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;
    protected $options;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Framework\Data\FormFactory     $formFactory
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Cpss\Crm\Model\RealStoreStatus $options,
        array $data = []
    ) 
    {
        $this->options = $options;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form.
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('row_data');
        $form = $this->_formFactory->create(
            ['data' => [
                            'id' => 'edit_form', 
                            'enctype' => 'multipart/form-data', 
                            'action' => $this->getData('action'), 
                            'method' => 'post'
                        ]
            ]
        );

        $form->setHtmlIdPrefix('rsgrid_');
        if ($model->getEntityId()) {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __("Real store info"), 'class' => 'fieldset-wide']
            );
            $fieldset->addField('entity_id', 'hidden', ['name' => 'entity_id']);
            $fieldset->addField(
                'shop_id',
                'text',
                [
                    'name' => 'shop_id',
                    'label' => __('Store Id'),
                    'id' => 'shop_id',
                    'title' => __('Store Id'),
                    'readonly' => true
                ]
            );
            $fieldset->addField(
                'shop_name',
                'text',
                [
                    'name' => 'shop_name',
                    'label' => __('Store Name'),
                    'id' => 'shop_name',
                    'title' => __('Store Name'),
                    'readonly' => true
                ]
            );
            $fieldset->addField(
                'shop_status',
                'select',
                [
                    'name' => 'shop_status',
                    'label' => __('Store Status'),
                    'id' => 'shop_status',
                    'title' => __('Store Status'),
                    'values' => $this->options->getOptionArray(),
                    'class' => 'status required-entry'
                ]
            );
    
            // $fieldset->addField(
            //     'shop_account',
            //     'text',
            //     [
            //         'name' => 'shop_account',
            //         'label' => __('Store API account'),
            //         'id' => 'shop_account',
            //         'title' => __('Store API account'),
            //         'class' => 'required-entry',
            //         'required' => true,
            //     ]
            // );
    
            $fieldset->addField(
                'shop_password',
                'password',
                [
                    'name' => 'shop_password',
                    'label' => __('Store API password'),
                    'id' => 'shop_password',
                    'title' => __('Store API password'),
                    'class' => 'required-entry',
                    'required' => true,
                ]
            );
    
            $fieldset->addField(
                'access_token',
                'text',
                [
                    'name' => 'access_token',
                    'label' => __('API access token'),
                    'id' => 'access_token',
                    'title' => __('API access token'),
                    'readonly' => true
                ]
            );

        }

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}