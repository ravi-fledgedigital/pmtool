<?php

namespace OnitsukaTigerIndo\SizeConverter\Controller\Adminhtml\Index;

class Delete extends \Magento\Backend\App\Action
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('size_id');

        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            $title = "";
            try {
                $model = $this->_objectManager->create('OnitsukaTigerIndo\SizeConverter\Model\IndoSize');
                $model->load($id);
                $model->delete();

                $this->messageManager->addSuccess(__('The size has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['size_id' => $id]);
            }
        }

        $this->messageManager->addError(__('We can\'t find a size to delete.'));

        return $resultRedirect->setPath('*/*/');
    }
}
