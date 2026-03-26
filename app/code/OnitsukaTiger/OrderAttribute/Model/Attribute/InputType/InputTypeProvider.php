<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Attribute\InputType;

use OnitsukaTiger\OrderAttribute\Block\Checkout\LayoutProcessor;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Option\ArrayInterface;

class InputTypeProvider implements ArrayInterface
{
    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\InputType[]
     */
    private $inputTypes;

    /**
     * @var FrontendCaster
     */
    private $frontendCaster;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    public function __construct(
        FrontendCaster $frontendCaster,
        CustomerSession $customerSession,
        $inputTypes = []
    ) {
        $this->inputTypes = $inputTypes;
        $this->frontendCaster = $frontendCaster;
        $this->customerSession = $customerSession;
    }

    /**
     * @return InputType[]
     */
    public function getList()
    {
        return $this->inputTypes;
    }

    /**
     * @param \Magento\Eav\Api\Data\AttributeInterface[] $attributes
     * @param string $providerName,
     * @param string $dataScope
     *
     * @return array
     */
    public function getFrontendElements($attributes, $providerName, $dataScope)
    {
        $result = [];

        /** @var \OnitsukaTiger\OrderAttribute\Model\Attribute\Attribute $attribute */
        foreach ($attributes as $attribute) {
            if ($element = $this->getFrontendElement($attribute, $providerName, $dataScope)) {
                $element['sortOrder'] = $attribute->getSortingOrder();

                if ($attribute->getIsHiddenFromCustomer()) {
                    $element['visible'] = 0;
                }

                $result[$attribute->getAttributeCode()] = $element;
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Eav\Api\Data\AttributeInterface $attribute
     * @param string $providerName,
     * @param string $dataScope
     *
     * @return array|bool
     */
    public function getFrontendElement($attribute, $providerName, $dataScope)
    {
        $currentGroup = $this->customerSession->getCustomerGroupId();
        if (!$attribute->isAllowedCustomerGroup($currentGroup)) {
            return false;
        }

        return $this->frontendCaster->cast(
            $attribute,
            $this->inputTypes[$attribute->getFrontendInput()],
            $providerName,
            $dataScope
        );
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->inputTypes as $code => $inputType) {
            $options[] = ['value' => $code, 'label' => $inputType->getLabel()];
        }

        return $options;
    }

    /**
     * Get options in "key-value" (input_type-label) format
     *
     * @return array
     */
    public function toArray()
    {
        $options = [];
        foreach ($this->inputTypes as $code => $inputType) {
            $options[$code] = $inputType->getLabel();
        }

        return $options;
    }

    /**
     * @param $code
     *
     * @return InputType|bool|mixed
     */
    public function getAttributeInputType($code)
    {
        if (empty($this->inputTypes[$code])) {
            return false;
        }

        return $this->inputTypes[$code];
    }

    /**
     * Return attribute input types with options for relation
     *
     * @return array
     */
    public function getInputTypesWithOptions()
    {
        $types = [];
        foreach ($this->inputTypes as $code => $inputType) {
            if ($inputType->isManageOptions()) {
                $types[] = $code;
            }
        }

        return $types;
    }

    /**
     * @return array
     */
    public function getInputValidationOptionArray()
    {
        $options = [['value' => '', 'label' => __('None')]];
        foreach ($this->getList() as $inputType) {
            foreach ($inputType->getValidateFilters() as $value => $label) {
                $options[] = ['value' => $value, 'label' => $label];
            }
        }

        return $options;
    }

    /**
     * @return array
     */
    public function getInputFilterOptionArray()
    {
        $options = [['value' => '', 'label' => __('None')]];
        foreach ($this->getList() as $inputType) {
            foreach ($inputType->getFilterTypes() as $value => $label) {
                $options[] = ['value' => $value, 'label' => $label];
            }
        }

        return $options;
    }
}
