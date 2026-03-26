<?php

namespace OnitsukaTigerKorea\Sales\Block\Adminhtml\Order\Address;

use Magento\Backend\Model\Session\Quote;
use Magento\Customer\Api\Data\AttributeMetadataInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use OnitsukaTigerKorea\Sales\Helper\Data;

class Form extends \Magento\Sales\Block\Adminhtml\Order\Address\Form
{
    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * Form constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param Quote $sessionQuote
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreate
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Customer\Model\Metadata\FormFactory $customerFormFactory
     * @param \Magento\Customer\Model\Options $options
     * @param \Magento\Customer\Helper\Address $addressHelper
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressService
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Framework\Registry $registry
     * @param Data $dataHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Customer\Model\Metadata\FormFactory $customerFormFactory,
        \Magento\Customer\Model\Options $options,
        \Magento\Customer\Helper\Address $addressHelper,
        \Magento\Customer\Api\AddressRepositoryInterface $addressService,
        \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Framework\Registry $registry,
        Data $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;

        parent::__construct($context, $sessionQuote, $orderCreate, $priceCurrency, $formFactory, $dataObjectProcessor, $directoryHelper, $jsonEncoder, $customerFormFactory, $options, $addressHelper, $addressService, $criteriaBuilder, $filterBuilder, $addressMapper, $registry, $data);
    }

    /**
     * Add rendering EAV attributes to Form element
     *
     * @param AttributeMetadataInterface[] $attributes
     * @param \Magento\Framework\Data\Form\AbstractForm $form
     * @return \Magento\Sales\Block\Adminhtml\Order\Create\Form\Address
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _addAttributesToForm($attributes, \Magento\Framework\Data\Form\AbstractForm $form)
    {
        // add additional form types
        $types = $this->_getAdditionalFormElementTypes();
        foreach ($types as $type => $className) {
            $form->addType($type, $className);
        }
        $renderers = $this->_getAdditionalFormElementRenderers();

        foreach ($attributes as $attribute) {
            $inputType = $attribute->getFrontendInput();

            if ($inputType) {
                $address = $this->_getAddress();
                $storeId = $address->getOrder()->getStoreId();
                if ($this->dataHelper->isSalesEnabled($storeId) && $attribute->getAttributeCode() == 'lastname') {
                    $element = $form->addField(
                        $attribute->getAttributeCode(),
                        $inputType,
                        [
                            'name' => $attribute->getAttributeCode(),
                            'label' => __($attribute->getStoreLabel()),
                            'class' => '',
                            'required' => false,
                        ]
                    );
                } else {
                    $element = $form->addField(
                        $attribute->getAttributeCode(),
                        $inputType,
                        [
                            'name' => $attribute->getAttributeCode(),
                            'label' => __($attribute->getStoreLabel()),
                            'class' => $this->getValidationClasses($attribute),
                            'required' => $attribute->isRequired(),
                        ]
                    );
                }
                if ($inputType == 'multiline') {
                    $element->setLineCount($attribute->getMultilineCount());
                }
                $element->setEntityAttribute($attribute);
                $this->_addAdditionalFormElementData($element);

                if (!empty($renderers[$attribute->getAttributeCode()])) {
                    $element->setRenderer($renderers[$attribute->getAttributeCode()]);
                }

                if ($inputType == 'select' || $inputType == 'multiselect') {
                    $options = [];
                    foreach ($attribute->getOptions() as $optionData) {
                        $data = $this->dataObjectProcessor->buildOutputDataArray(
                            $optionData,
                            \Magento\Customer\Api\Data\OptionInterface::class
                        );
                        foreach ($data as $key => $value) {
                            if (is_array($value)) {
                                unset($data[$key]);
                                $data['value'] = $value;
                            }
                        }
                        $options[] = $data;
                    }
                    $element->setValues($options);
                } elseif ($inputType == 'date') {
                    $format = $this->_localeDate->getDateFormat(
                        \IntlDateFormatter::SHORT
                    );
                    $element->setDateFormat($format);
                }
            }
        }

        return $this;
    }

    /**
     * Retrieve frontend classes according validation rules
     *
     * @param AttributeMetadataInterface $attribute
     *
     * @return string
     */
    private function getValidationClasses(AttributeMetadataInterface $attribute) : string
    {
        $out = [];
        $out[] = $attribute->getFrontendClass();

        $textClasses = $this->getTextLengthValidateClasses($attribute);
        if (!empty($textClasses)) {
            $out = array_merge($out, $textClasses);
        }

        $out = !empty($out) ? implode(' ', array_unique(array_filter($out))) : '';
        return $out;
    }

    /**
     * Retrieve validation classes by min_text_length and max_text_length rules
     *
     * @param AttributeMetadataInterface $attribute
     *
     * @return array
     */
    private function getTextLengthValidateClasses(AttributeMetadataInterface $attribute) : array
    {
        $classes = [];

        $validateRules = $attribute->getValidationRules();
        if (!empty($validateRules)) {
            foreach ($validateRules as $rule) {
                switch ($rule->getName()) {
                    case 'min_text_length':
                        $classes[] = 'minimum-length-' . $rule->getValue();
                        break;

                    case 'max_text_length':
                        $classes[] = 'maximum-length-' . $rule->getValue();
                        break;
                }
            }

            if (!empty($classes)) {
                $classes[] = 'validate-length';
            }
        }

        return $classes;
    }
}
