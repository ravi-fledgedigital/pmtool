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
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\CatalogLabel\Controller\Adminhtml\Label;


use Magento\Framework\Controller\ResultFactory;
use Mirasvit\CatalogLabel\Controller\Adminhtml\Label;

class Apply extends Label
{
    public function execute()
    {
        $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $model = $this->getModel();
        if ($model->getId()) {
            try {
                $this->indexer->reindexLabel((int) $model->getId());
                $this->messageManager->addSuccess((string)__('Label was successfully applied'));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }

            $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);

            return;
        }

        $this->messageManager->addError((string)__('Unable to find label to apply'));
        $this->_redirect('*/*/');
    }
}
