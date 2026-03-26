<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LayeredNavigation\Controller\Adminhtml\Filter;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Eav\Model\Config as EavConfig;

class SaveEdit extends Action
{
    private $resultJsonFactory;
    private $eavConfig;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        EavConfig $eavConfig
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->eavConfig = $eavConfig;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $request = $this->getRequest();

        try {
            $attributeId = (int)$request->getParam('attribute_id');
            $frontendLabel = (string)$request->getParam('frontend_label');
            $isFilterable = (int)$request->getParam('is_filterable');

            $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeId);

            if (!$attribute || !$attribute->getId()) {
                throw new \Exception(__('Attribute was not found'));
            }

            $attribute->setFrontendLabel($frontendLabel);
            $attribute->setIsFilterable($isFilterable);
            $attribute->save();

            return $result->setData([
                'success' => true,
                'message' => __('Attribute is updated'),
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
