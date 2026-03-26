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


namespace Mirasvit\CatalogLabel\Controller\Adminhtml\Template;


use Magento\Framework\Controller\ResultFactory;
use Mirasvit\CatalogLabel\Controller\Adminhtml\Template;

class Edit extends Template
{
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $id    = $this->getRequest()->getParam('id');
        $model = $this->getModel();

        if ($id && !$model) {
            $this->messageManager->addErrorMessage(__('This template no longer exists.'));

            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $this->initPage($resultPage)
            ->getConfig()
            ->getTitle()
            ->prepend(
                $id ? (string)__('Template "%1"', $model->getName()) : (string)__('New Template')
            );

        return $resultPage;
    }
}
