<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Attribute;

use OnitsukaTiger\OrderAttribute\Api\CheckoutAttributeRepositoryInterface;
use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface;
use OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Attribute;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity;
use Laminas\Uri\Uri;
use Magento\Backend\App\Action\Context;
use Magento\Eav\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;

class Save extends Attribute
{
    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var CheckoutAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var Uri
     */
    private $uri;

    public function __construct(
        Context $context,
        Config $eavConfig,
        CheckoutAttributeRepositoryInterface $attributeRepository,
        Uri $uri
    ) {
        parent::__construct($context);
        $this->eavConfig = $eavConfig;
        $this->attributeRepository = $attributeRepository;
        $this->uri = $uri;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            $this->messageManager->addErrorMessage(
                __('Post Data is Empty')
            );

            return $this->_redirect('*/*/edit', ['_current' => true]);
        }

        $redirectBack = $this->getRequest()->getParam('back', false);

        $attributeId = (int)$this->getRequest()->getParam('attribute_id', 0);
        if ($attributeId) {
            try {
                $attribute = $this->attributeRepository->getById($attributeId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->_session->setAttributeData($data);

                return $this->_redirect('*/*/create');
            }
        } else {
            $attribute = $this->eavConfig->getAttribute(Entity::ENTITY_TYPE_CODE, $data['attribute_code']);
        }

        try {
            $data = $this->preparePostData($data, $attribute);
            $attribute->addData($data);
            $this->attributeRepository->save($attribute);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_session->setAttributeData($data);

            return $this->_redirect(
                '*/*/edit',
                ['_current' => true]
            );
        }

        $this->messageManager->addSuccessMessage(__('Order attribute was successfully saved.'));
        $this->_session->setAttributeData(false);

        if ($redirectBack) {
            return $this->_redirect(
                '*/*/edit',
                [
                    'attribute_id' => $attribute->getId(),
                    '_current'     => true
                ]
            );
        }

        return $this->_redirect('*/*/', []);
    }

    /**
     * @param array $data
     * @param CheckoutAttributeInterface|\OnitsukaTiger\OrderAttribute\Model\Attribute\Attribute $attribute
     *
     * @return array
     */
    protected function preparePostData($data, $attribute): array
    {
        if (!$attribute->getFrontendInput()) {
            $attribute->setFrontendInput($data[CheckoutAttributeInterface::FRONTEND_INPUT]);
        }

        $this->preprocessOptionsData($data);

        $defaultValueField = $attribute->getDefaultValueByInput($attribute->getFrontendInput());
        if ($defaultValueField) {
            $data['default_value'] = $data[$defaultValueField];
        } else {
            $data['default_value'] = '';
        }

        $data[CheckoutAttributeInterface::REQUIRED_ON_FRONT_ONLY] = false;
        if ($data[CheckoutAttributeInterface::IS_REQUIRED] == CheckoutAttributeInterface::IS_REQUIRED_PROXY_VALUE) {
            $data[CheckoutAttributeInterface::REQUIRED_ON_FRONT_ONLY] = true;
            $data[CheckoutAttributeInterface::IS_REQUIRED] = false;
        }

        $data = $this->prepareValidationRules($data, $attribute);

        return $data;
    }

    /**
     * Magento 2.2.6 options support
     * @param $data
     */
    protected function preprocessOptionsData(&$data)
    {
        if (isset($data['serialized_options'])) {
            $serializedOptions = json_decode($data['serialized_options'], true);
            $defaults = [];
            foreach ($serializedOptions as $serializedOption) {
                $this->uri->setQuery($serializedOption);
                $option = $this->uri->getQueryAsArray();
                if (isset($option['default'][0])) {
                    $defaults[] = $option['default'][0];
                    unset($option['default'][0]);
                }
                $currentOption = current($option['option']['value']);
                if (empty($currentOption[Store::DEFAULT_STORE_ID])) {
                    throw new LocalizedException(__('Admin option value is required'));
                }

                $data = array_replace_recursive($data, $option);
            }
            $data['default'] = $defaults;
            unset($data['serialized_options']);
        }
    }

    /**
     * @param array                                                                  $data
     * @param CheckoutAttributeInterface|\OnitsukaTiger\OrderAttribute\Model\Attribute\Attribute $attribute
     *
     * @throws CouldNotSaveException
     * @return array
     */
    protected function prepareValidationRules($data, $attribute)
    {
        $rules = [];
        $inputConfiguration = $attribute->getInputTypeConfiguration();

        foreach ($inputConfiguration->getValidateTypes() as $validateType) {
            if (!empty($data[$validateType])) {
                $rules[$validateType] = $data[$validateType];
            } elseif (!empty($data['scope_' . $validateType])) {
                $rules[$validateType] = $data['scope_' . $validateType];
            }
            if (in_array($validateType, ['date_range_min', 'date_range_max']) && !empty($rules[$validateType])) {
                $rules[$validateType] = (new \DateTime($rules[$validateType]))->getTimestamp();
                if ($attribute->getFrontendInput() == 'datetime' && $validateType == 'date_range_max') {
                    $rules[$validateType] += 86399;
                }
            }
        }

        if ($attribute->getFrontendInput() === 'file') {
            $rules['max_file_size'] = isset($rules['max_file_size'])
                ? (int)$rules['max_file_size']
                : 0;
        }

        if (!empty($data['input_validation'])
            && in_array($data['input_validation'], array_keys($inputConfiguration->getValidateFilters()))
        ) {
            $rules['input_validation'] = $data['input_validation'];
        }

        $data[CheckoutAttributeInterface::VALIDATE_RULES] = null;
        if (!empty($rules)) {
            $data[CheckoutAttributeInterface::VALIDATE_RULES] = $rules;
        }

        return $data;
    }
}
