<?php

namespace OnitsukaTigerIndo\RmaAccount\Controller\Adminhtml\Items;

class Edit extends \OnitsukaTigerIndo\RmaAccount\Controller\Adminhtml\Items
{
    /**
     * Execute method
     *
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        $model = $this->_objectManager->create(\OnitsukaTigerIndo\RmaAccount\Model\RmaAccount::class);

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This rma account details form no longer exists.'));
                $this->_redirect('onitsukatigerindo_rmaaccount/*');
                return;
            }
        }
        // set entered data if was error when we do save
        $data = $this->_objectManager->get(\Magento\Backend\Model\Session::class)->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        }
        $this->_coreRegistry->register('current_onitsukatigerindo_rmaaccount_items', $model);
        $this->_initAction();
        $this->_view->getLayout()->getBlock('items_items_edit');
        $this->_view->renderLayout();
    }
}
