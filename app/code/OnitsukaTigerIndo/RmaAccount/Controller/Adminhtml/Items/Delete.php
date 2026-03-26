<?php

namespace OnitsukaTigerIndo\RmaAccount\Controller\Adminhtml\Items;

use OnitsukaTigerIndo\RmaAccount\Model\RmaAccount;
use Psr\Log\LoggerInterface;

class Delete extends \OnitsukaTigerIndo\RmaAccount\Controller\Adminhtml\Items
{
    /**
     * Delete Method
     *
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $model = $this->_objectManager->create(RmaAccount::class);
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('You deleted the rma account details form.'));
                $this->_redirect('onitsukatigerindo_rmaaccount/*/');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('We can\'t delete item right now. Please review the log and try again.')
                );
                $this->_objectManager->get(LoggerInterface::class)->critical($e);
                $this->_redirect('onitsukatigerindo_rmaaccount/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->messageManager->addError(__('We can\'t find a rma account details form to delete.'));
        $this->_redirect('onitsukatigerindo_rmaaccount/*/');
    }
}
