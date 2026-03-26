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
use Magento\Backend\App\Action\Context;
use Magento\Eav\Model\Config;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Setup\Exception;
use Magento\Store\Model\Store;
use Laminas\Uri\Uri;

class Validate extends Attribute
{
    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

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
        DataObjectFactory $dataObjectFactory,
        CheckoutAttributeRepositoryInterface $attributeRepository,
        Uri $uri
    ) {
        parent::__construct($context);
        $this->eavConfig = $eavConfig;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->attributeRepository = $attributeRepository;
        $this->uri = $uri;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\DataObject $response */
        $response = $this->dataObjectFactory->create();
        $response->setError(false);
        $data = $this->getRequest()->getParams();
        try {
            $attribute = $this->retrieveAttributeFromRequest($response, $data);
            $this->validateExistingAttribute($attribute, $data);
            $this->validateEmptyOptions($data);
            $this->validateDateTime($attribute, $data);
        } catch (\Exception $e) {
            $response->setError(true);
            $response->setMessage($e->getMessage());
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setJsonData($response->toJson());
    }

    /**
     * @param $response
     * @param $data
     * @return CheckoutAttributeInterface|\Magento\Eav\Model\Entity\Attribute\AbstractAttribute|mixed|null
     */
    private function retrieveAttributeFromRequest($response, $data)
    {
        $attributeId = (int)$this->getRequest()->getParam('attribute_id', 0);
        if ($attributeId) {
            $attribute = $this->attributeRepository->getById($attributeId);
            $this->_session->setAttributeData($data);
        } else {
            $attribute = $this->eavConfig->getAttribute(
                Entity::ENTITY_TYPE_CODE,
                $this->getRequest()->getParam('attribute_code')
            );
        }
        $attribute->addData($data);

        return $attribute;
    }

    /**
     * @param $attribute
     * @param $data
     * @return bool|mixed
     * @throws LocalizedException
     */
    private function validateExistingAttribute($attribute, $data)
    {
        if (!empty($data['attribute_code'])) {
            $attributeCode = $data['attribute_code'];
            $attributeId = (int)$this->getRequest()->getParam('attribute_id', 0);
            if ($attribute->getId() && !$attributeId) {
                if (strlen($this->getRequest()->getParam('attribute_code'))) {
                    throw new LocalizedException(__('An attribute with this code already exists.'));
                }

                throw new LocalizedException(
                    __('An attribute with the same code (%1) already exists.', $attributeCode)
                );
            }
        }

        return true;
    }

    /**
     * @param $data
     * @return bool|mixed
     * @throws LocalizedException
     */
    private function validateEmptyOptions($data)
    {
        $serializedOptions = json_decode($data['serialized_options'], true);
        if (!empty($serializedOptions) && empty($data['option'])) {
            foreach ($serializedOptions as $serializedOption) {
                $this->uri->setQuery($serializedOption);
                $option = $this->uri->getQueryAsArray();
                $currentOption = current($option['option']['value']);
                if (empty($currentOption[Store::DEFAULT_STORE_ID])) {
                    throw new LocalizedException(__('Admin option value is required'));
                }
            }
        }

        return true;
    }

    /**
     * @param $attribute
     * @param $data
     * @return mixed
     * @throws LocalizedException
     */
    private function validateDateTime($attribute, $data)
    {
        $inputConfiguration = $attribute->getInputTypeConfiguration();
        if ($inputConfiguration) {
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
        }

        if (in_array($attribute->getFrontendInput(), ['date', 'datetime'])) {
            if (!empty($data['default_value_date'])) {
                $defaultValueTime = (new \DateTime($data['default_value_date']))->getTimestamp();
            } else {
                $defaultValueTime = false;
            }

            if (!empty($rules['date_range_min'])
                && $defaultValueTime && ($defaultValueTime < $rules['date_range_min'])
            ) {
                throw new LocalizedException(__('Default Date less than Minimum Date'));
            }

            if (!empty($rules['date_range_max'])
                && $defaultValueTime && ($defaultValueTime > $rules['date_range_max'])
            ) {
                throw new LocalizedException(__('Default Date more than Maximum Date'));
            }

            if ((!empty($rules['date_range_min']) && !empty($rules['date_range_max']))
                && $rules['date_range_min'] > $rules['date_range_max']
            ) {
                throw new LocalizedException(__('Minimum Date more than Maximum Date'));
            }
        }

        return true;
    }
}
