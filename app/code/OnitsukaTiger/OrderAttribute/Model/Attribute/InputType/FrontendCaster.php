<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Attribute\InputType;

use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface;
use OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\FrontendCaster\SpecificationProcessorInterface;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\RelationDetails\CollectionFactory;
use OnitsukaTiger\OrderAttribute\Model\Value\LastCheckoutValue;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

class FrontendCaster
{
    /**
     * EAV attribute properties to fetch from meta storage
     * @var array
     */
    protected $metaPropertiesMap = [
        'dataType' => 'getFrontendInput',
        'visible' => 'getIsVisibleOnFront',
        'required' => 'getIsRequired',
        'notice' => 'getNote',
        'default' => 'getDefaultOrLastValue',
    ];

    /**
     * @var CollectionFactory
     */
    private $relationCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var int
     */
    private $sortOrder = 0;

    /**
     * @var LastCheckoutValue
     */
    private $lastCheckoutValue;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var string[]
     */
    private $specificationProcessorClasses;

    public function __construct(
        CollectionFactory $relationCollectionFactory,
        StoreManagerInterface $storeManager,
        LastCheckoutValue $lastCheckoutValue,
        ObjectManagerInterface $objectManager,
        array $specificationProcessorClasses = []
    ) {
        $this->relationCollectionFactory = $relationCollectionFactory;
        $this->storeManager = $storeManager;
        $this->lastCheckoutValue = $lastCheckoutValue;
        $this->objectManager = $objectManager;
        $this->specificationProcessorClasses = $specificationProcessorClasses;
    }

    /**
     * @param \Magento\Eav\Api\Data\AttributeInterface              $attribute
     * @param \OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\InputType $inputType
     * @param string                                                $providerName
     * @param string                                                $dataScope
     *
     * @return array|bool
     */
    public function cast($attribute, $inputType, $providerName, $dataScope)
    {
        $element = [
            'formElement' => $inputType->getFrontendInputType(),
            'label' => __($attribute->getStoreLabel())
        ];

        foreach ($this->metaPropertiesMap as $metaName => $methodName) {
            $value = $attribute->$methodName();
            $element[$metaName] = $value;
        }

        $element['sortOrder'] = $this->sortOrder++;

        if (!$element['visible']) {
            return false;
        }

        if ($inputType->getSourceModel()) {
            $this->setOptions($element, $attribute, $inputType);
        }

        $element['frontend_class'] = $attribute->getFrontend()->getClass();

        if (!empty($element['frontend_class'])) {
            foreach (explode(' ', $element['frontend_class']) as $key) {
                $element['validation'][$key] = true;
            }
        }

        $validateRules = $attribute->getValidateRules();
        if (!empty($validateRules['min_text_length'])) {
            $element['validation']['min_text_length'] = $validateRules['min_text_length'];
        }
        if (!empty($validateRules['max_text_length'])) {
            $element['validation']['max_text_length'] = $validateRules['max_text_length'];
        }

        $element['shipping_methods'] = $attribute->getShippingMethods();

        $tooltips = $attribute->getStoreTooltips();
        if (!empty($tooltips[$this->storeManager->getStore()->getId()])) {
            $element['config']['tooltip']['description'] = $tooltips[$this->storeManager->getStore()->getId()];
        }

        $this->setElementRelations($element, $attribute, $inputType);

        if ($attribute->isSaveToFutureCheckout()
            && (($value = $this->lastCheckoutValue->retrieve($attribute)) !== null)
            && $value !== false
            && $value !== ""
        ) {
            $element['value'] = $value;
        } elseif ($element['default'] !== null) {
            $element['value'] = $element['default'];
        }
        unset($element['default']);

        $this->setSpecificAttributeOptions($element, $attribute, $inputType);

        $element = array_merge_recursive(
            $element,
            [
                'component' => $inputType->getFrontendUiComponent(),
                'config' => [
                    'customScope' => $dataScope,
                    'template' => 'ui/form/field',
                    'elementTmpl' => !empty($inputType->getFrontendTmpl())
                        ? $inputType->getFrontendTmpl()
                        : 'ui/form/element/' . $element['formElement']
                ],
                'dataScope' => $dataScope . '.' . $attribute->getAttributeCode(),
                'provider' => $providerName
            ]
        );

        return $element;
    }

    /**
     * @param array                                                 &$element
     * @param \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface $attribute
     * @param \OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\InputType &$inputType
     *
     * @return void
     */
    protected function setOptions(&$element, $attribute, &$inputType)
    {
        $allOptions = $attribute->getSource()->getAllOptions(false);

        if ($inputType->isDisplayEmptyOption()) {
            array_unshift($allOptions, ['label' => ' ', 'value' => '']);
        }
        $element['options'] = $allOptions;
    }

    /**
     * @param array &$element
     * @param CheckoutAttributeInterface $attribute
     * @param InputType &$inputType
     *
     * @return void
     */
    protected function setSpecificAttributeOptions(
        array &$element,
        CheckoutAttributeInterface $attribute,
        InputType &$inputType
    ): void {
        $processor = $this->getSpecificationProcessor($inputType->getFrontendInputType());

        if ($processor instanceof SpecificationProcessorInterface) {
            $processor->processSpecificationByAttribute($element, $attribute);
        }
    }

    /**
     * @param array                                                 &$element
     * @param \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface $attribute
     * @param \OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\InputType &$inputType
     *
     * @return void
     */
    protected function setElementRelations(&$element, $attribute, &$inputType)
    {
        if ($inputType->isManageOptions()) {
            $element['relations'] = $this->relationCollectionFactory->create()
                ->getAttributeRelations($attribute->getAttributeId());
        }
    }

    /**
     * @param string $frontendInputType
     * @return SpecificationProcessorInterface|null
     */
    private function getSpecificationProcessor(string $frontendInputType): ?SpecificationProcessorInterface
    {
        $processorClass = $this->specificationProcessorClasses[$frontendInputType] ?? null;

        if ($processorClass) {
            return $this->objectManager->get($processorClass);
        }

        return null;
    }
}
