<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Gthk\Controller\Adminhtml\Gthk;

class Delete extends \OnitsukaTiger\Gthk\Controller\Adminhtml\Gthk
{

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('gthk_id');
        if ($id) {
            try {
                $model = $this->_objectManager->create(\OnitsukaTiger\Gthk\Model\Gthk::class);
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccessMessage(__('You deleted the GHTK.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['gthk_id' => $id]);
            }
        }
        $this->messageManager->addErrorMessage(__('We can\'t find a GHTK to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}

