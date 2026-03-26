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


use Mirasvit\LayeredNavigation\Api\Data\GroupInterface;
use Mirasvit\LayeredNavigation\Controller\Adminhtml\Group;

class Delete extends Group
{
    public function execute()
    {
        $model = $this->initModel();

        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$model->getId()) {
            $this->messageManager->addErrorMessage((string)__('This grouped options no longer exist.'));

            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->repository->delete($model);

            $this->messageManager->addSuccessMessage((string)__('The grouped options has been deleted.'));

            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $resultRedirect->setPath('*/*/edit', [GroupInterface::ID => $model->getId()]);
        }
    }
}
