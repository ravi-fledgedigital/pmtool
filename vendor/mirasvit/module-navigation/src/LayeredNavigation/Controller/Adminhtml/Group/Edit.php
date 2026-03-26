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


namespace Mirasvit\LayeredNavigation\Controller\Adminhtml\Group;


use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Page\Interceptor;
use Magento\Framework\Controller\ResultFactory;
use Mirasvit\LayeredNavigation\Api\Data\GroupInterface;
use Mirasvit\LayeredNavigation\Controller\Adminhtml\Group;

class Edit extends Group
{
    public function execute(): Page
    {
        /** @var Interceptor $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $id    = $this->getRequest()->getParam(GroupInterface::ID);
        $model = $this->initModel();

        if ($id && !$model->getId()) {
            $this->messageManager->addError((string)__('This grouped options no longer exist.'));

            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $this->initPage($resultPage)
            ->getConfig()
            ->getTitle()
            ->prepend($model->getId() ? $model->getTitle()[0]['label'] : (string)__('New Group'));

        return $resultPage;
    }
}
