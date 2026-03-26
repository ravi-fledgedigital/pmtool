<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Controller\Adminhtml\Attributes;

use Amasty\CustomTabs\Model\Attribute\Attribute;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;

class WysiwygPlugin extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Attribute
     */
    private $attributeModel;

    public function __construct(
        Attribute $attributeModel,
        JsonFactory $jsonFactory,
        Action\Context $context
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->attributeModel = $attributeModel;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $attributesVariables = $this->attributeModel->getVariablesOptionArray();

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        return $resultJson->setData([$attributesVariables]);
    }
}
