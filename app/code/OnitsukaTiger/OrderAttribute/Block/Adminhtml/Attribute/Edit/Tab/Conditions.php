<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Block\Adminhtml\Attribute\Edit\Tab;

class Conditions extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Attribute\Attribute
     */
    protected $attribute;

    /**
     * @var \Magento\Shipping\Model\Config
     */
    private $shippingConfig;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\ConfigProvider
     */
    private $configProvider;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Shipping\Model\Config $shippingConfig,
        \OnitsukaTiger\OrderAttribute\Model\ConfigProvider $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->shippingConfig = $shippingConfig;
        $this->configProvider = $configProvider;
    }

    /**
     * Shipping method options
     *
     * @return array
     */
    protected function getActiveShippingMethods()
    {
        $methods = [];

        $activeCarriers = $this->shippingConfig->getActiveCarriers();

        foreach ($activeCarriers as $carrierCode => $carrierModel) {
            $options = [];
            if ($carrierMethods = $carrierModel->getAllowedMethods()) {
                foreach ($carrierMethods as $methodCode => $method) {
                    $code = $carrierCode . '_' . $methodCode;
                    $options[] = ['value' => $code, 'label' => $method?:$code];
                }
            }
            $carrierTitle = $this->configProvider->getCarrierTitle($carrierCode);
            $methods[] = ['value' => $options, 'label' => $carrierTitle];
        }

        return $methods;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /**
         * @var \OnitsukaTiger\OrderAttribute\Model\Attribute\Attribute $model
         */
        $model = $this->getAttributeObject();
        $formData = [];

        if ($currentShippingMethods = $model->getShippingMethods()) {
            $formData = $currentShippingMethods;
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'     => 'edit_form',
                    'action' => $this->getData('action'),
                    'method' => 'post'
                ]
            ]
        );

        $fieldset = $form->addFieldset(
            'conditions_fieldset',
            ['legend' => __('Manage Conditions')]
        );

        $fieldset->addField(
            'shipping_methods',
            'multiselect',
            [
                'name'   => 'shipping_methods[]',
                'label'  => __('Shipping Methods'),
                'title'  => __('Shipping Methods'),
                'note'   => __('Please, note that if shipping methods are NOT selected in the field,
                    order attributes will be displayed on the checkout page right after the page load.
                    And if any is selected, order attributes will appear on the checkout page just when
                    a user selects the shipping method'),
                'values' => $this->getActiveShippingMethods(),
            ]
        );

        $form->addValues(
            [
                'shipping_methods' => $formData
            ]
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Return attribute object
     *
     * @return \OnitsukaTiger\OrderAttribute\Model\Attribute\Attribute
     */
    public function getAttributeObject()
    {
        if (null === $this->attribute) {
            return $this->_coreRegistry->registry('entity_attribute');
        }

        return $this->attribute;
    }
}
