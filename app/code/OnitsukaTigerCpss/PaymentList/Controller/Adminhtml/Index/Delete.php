<?php

namespace OnitsukaTigerCpss\PaymentList\Controller\Adminhtml\Index;

class Delete extends \Magento\Backend\App\Action
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('payment_id');

        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $model = $this->_objectManager->create(\OnitsukaTigerCpss\PaymentList\Model\PaymentMethod::class);
                $model->load($id);
                $model->delete();

                $this->messageManager->addSuccess(__('The payment method has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['payment_id' => $id]);
            }
        }

        $this->messageManager->addError(__('We can\'t find a payment method to delete.'));

        return $resultRedirect->setPath('*/*/');
    }
}
