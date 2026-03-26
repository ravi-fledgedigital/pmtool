<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Attribute;

use OnitsukaTiger\OrderAttribute\Api\CheckoutAttributeRepositoryInterface;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity;
use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface;
use OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\InputTypeProvider;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\InputException;
use Laminas\Validator\Regex;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Repository implements CheckoutAttributeRepositoryInterface
{
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    private $attributeResource;

    /**
     * @var \Magento\Framework\Filter\FilterManager
     */
    private $filterManager;

    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    private $eavAttributeRepository;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var \Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype\ValidatorFactory
     */
    private $validatorFactory;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterfaceFactory
     */
    private $attributeFactory;

    /**
     * @var InputTypeProvider
     */
    private $inputTypeProvider;

    public function __construct(
        \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Attribute $attributeResource,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Eav\Api\AttributeRepositoryInterface $eavAttributeRepository,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype\ValidatorFactory $validatorFactory,
        \OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\InputTypeProvider $inputTypeProvider,
        \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterfaceFactory $attributeFactory
    ) {
        $this->attributeResource = $attributeResource;
        $this->filterManager = $filterManager;
        $this->eavAttributeRepository = $eavAttributeRepository;
        $this->eavConfig = $eavConfig;
        $this->validatorFactory = $validatorFactory;
        $this->attributeFactory = $attributeFactory;
        $this->inputTypeProvider = $inputTypeProvider;
    }

    /**
     * @inheritdoc
     */
    public function get($attributeCode)
    {
        return $this->eavAttributeRepository->get(
            Entity::ENTITY_TYPE_CODE,
            $attributeCode
        );
    }

    /**
     * @inheritdoc
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        return $this->eavAttributeRepository->getList(
            Entity::ENTITY_TYPE_CODE,
            $searchCriteria
        );
    }

    /**
     * @inheritdoc
     */
    public function getById($attributeId)
    {
        /** @var CheckoutAttributeInterface $attribute */
        $attribute = $this->attributeFactory->create();
        $this->attributeResource->load($attribute, $attributeId);
        if (!$attribute || !$attribute->getAttributeId()) {
            throw new NoSuchEntityException(
                __('Attribute with ID "%1" does not exist.', $attributeId)
            );
        }
        if ($attribute->getEntityType()->getEntityTypeCode() !== Entity::ENTITY_TYPE_CODE) {
            throw new StateException(
                __('Attribute with ID "%1" is not Order Attribute.', $attributeId)
            );
        }
        return $attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CheckoutAttributeInterface $attribute)
    {
        if ($attribute->getAttributeId()) {
            $existingModel = $this->getById($attribute->getAttributeId());

            // Attribute code must not be changed after attribute creation
            $attribute->setAttributeCode($existingModel->getAttributeCode());
            $attribute->setAttributeId($existingModel->getAttributeId());
            $attribute->setIsUserDefined($existingModel->getIsUserDefined());
            $attribute->setFrontendInput($existingModel->getFrontendInput());
        } else {
            $attribute->setAttributeId(null);

            $this->validateCode($attribute->getAttributeCode());
            if ($attribute->getFrontendInput() !== 'html') {
                $this->validateFrontendInput($attribute->getFrontendInput());
            }
            $inputType = $this->inputTypeProvider->getAttributeInputType($attribute->getFrontendInput());
            $attribute->setBackendType($inputType->getBackendType());
            $attribute->setSourceModel($inputType->getSourceModel());
            $attribute->setBackendModel($inputType->getBackendModel());
            $attribute->setEntityTypeId(
                $this->eavConfig
                    ->getEntityType(Entity::ENTITY_TYPE_CODE)
                    ->getId()
            );
            $attribute->setIsUserDefined(1);
        }

        if ($attribute->getBackendType() == 'varchar' && !isset($attribute->getValidationRules()['max_text_length'])) {
            $validationRules = $attribute->getValidationRules();
            //DB_MAX_VARCHAR_LENGTH
            $validationRules['max_text_length'] = 255;
            $attribute->setValidationRules($validationRules);
        }
        if ($attribute->getFrontendInput() === 'file') {
            $attribute->setSaveToFutureCheckout(false);
        }

        if (!$attribute->getFrontendLabels() && !$attribute->getDefaultFrontendLabel()) {
            throw InputException::requiredField('frontend_label');
        }

        $this->attributeResource->save($attribute);
        return $this->get($attribute->getAttributeCode());
    }

    /**
     * {@inheritdoc}
     */
    public function delete(CheckoutAttributeInterface $attribute)
    {
        $this->attributeResource->delete($attribute);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($attributeCode)
    {
        $this->delete(
            $this->get($attributeCode)
        );
        return true;
    }

    /**
     * @param CheckoutAttributeInterface $attribute
     */
    protected function prepareOptions(CheckoutAttributeInterface $attribute)
    {
        if (!empty($attribute->getData(AttributeInterface::OPTIONS))) {
            $options = [];
            $sortOrder = 0;
            $default = [];
            $optionIndex = 0;
            foreach ($attribute->getOptions() as $option) {
                $optionIndex++;
                $optionId = $option->getValue() ?: 'option_' . $optionIndex;
                $options['value'][$optionId][0] = $option->getLabel();
                $options['order'][$optionId] = $option->getSortOrder() ?: $sortOrder++;
                if (is_array($option->getStoreLabels())) {
                    foreach ($option->getStoreLabels() as $label) {
                        $options['value'][$optionId][$label->getStoreId()] = $label->getLabel();
                    }
                }
                if ($option->getIsDefault()) {
                    $default[] = $optionId;
                }
            }
            $attribute->setDefault($default);
            if (count($options)) {
                $attribute->setOption($options);
            }
        }
    }

    /**
     * Validate attribute code
     *
     * @param string $code
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     */
    protected function validateCode($code)
    {
        $validatorAttrCode = new Regex(['pattern' => '/^[a-z][a-z_0-9]{0,30}$/']);
        if (!$validatorAttrCode->isValid($code)) {
            throw InputException::invalidFieldValue('attribute_code', $code);
        }
    }

    /**
     * Validate Frontend Input Type
     *
     * @param  string $frontendInput
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     */
    protected function validateFrontendInput($frontendInput)
    {
        /** @var \Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype\Validator $validator */
        $validator = $this->validatorFactory->create();
        if (!$validator->isValid($frontendInput)) {
            throw InputException::invalidFieldValue('frontend_input', $frontendInput);
        }
    }
}
