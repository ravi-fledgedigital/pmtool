<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Relation;

use OnitsukaTiger\OrderAttribute\Model\Attribute\Relation\AttributeOptionsProvider;
use OnitsukaTiger\OrderAttribute\Model\Attribute\Relation\DependentAttributeProvider;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class UpdateParentAttribute extends \OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Relation
{
    /**
     * @var AttributeOptionsProvider
     */
    private $optionsProvider;

    /**
     * @var DependentAttributeProvider
     */
    private $attributeProvider;

    public function __construct(
        Action\Context $context,
        AttributeOptionsProvider $optionsProvider,
        DependentAttributeProvider $attributeProvider
    ) {
        parent::__construct($context);
        $this->optionsProvider = $optionsProvider;
        $this->attributeProvider = $attributeProvider;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $attributeId = $this->getRequest()->getParam('attribute_id');
        $response = [
            'error' => __('The attribute_id is not defined. Please try to reload the page. ')
        ];
        if ($attributeId) {
            try {
                $attributeOptions = $this->optionsProvider->setParentAttributeId($attributeId)->toOptionArray();
                $dependentAttributes = $this->attributeProvider->setParentAttributeId($attributeId)->toOptionArray();
                $response = [
                    'attribute_options' => $attributeOptions,
                    'dependent_attributes' => $dependentAttributes,
                    'error' => 0
                ];
            } catch (\Exception $exception) {
                $response = [
                    'error' => $exception->getMessage()
                ];
            }
        }

        $resultJson->setData($response);

        return $resultJson;
    }
}
