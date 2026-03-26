<?php
//phpcs:ignoreFile

namespace OnitsukaTigerIndo\RmaAccount\Controller\Adminhtml\Items;

use Magento\Backend\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use OnitsukaTigerIndo\RmaAccount\Model\RmaAccount;
use Psr\Log\LoggerInterface;

class Save extends \OnitsukaTigerIndo\RmaAccount\Controller\Adminhtml\Items
{
    /**
     * Execute Method
     *
     * @return void
     */
    public function execute()
    {
        if ($this->getRequest()->getPostValue()) {
            try {
                $model = $this->_objectManager->create(RmaAccount::class);
                $data = $this->getRequest()->getPostValue();
                $inputFilter = new \Zend_Filter_Input(
                    [],
                    [],
                    $data
                );
                $data = $inputFilter->getUnescaped();
                $id = $this->getRequest()->getParam('id');
                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        throw new LocalizedException(__('The wrong item is specified.'));
                    }
                }
                $model->setData($data);
                $session = $this->_objectManager->get(Session::class);
                $session->setPageData($model->getData());
                $model->save();
                $this->messageManager->addSuccess(__('You saved the RMA Account Details Form.'));
                $session->setPageData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('onitsukatigerindo_rmaaccount/*/edit', ['id' => $model->getId()]);
                    return;
                }
                $this->_redirect('onitsukatigerindo_rmaaccount/*/');
                return;
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $id = (int)$this->getRequest()->getParam('id');
                if (!empty($id)) {
                    $this->_redirect('onitsukatigerindo_rmaaccount/*/edit', ['id' => $id]);
                } else {
                    $this->_redirect('onitsukatigerindo_rmaaccount/*/new');
                }
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('Something went wrong while saving the item data. Please review the error log.')
                );
                $this->_objectManager->get(LoggerInterface::class)->critical($e);
                $this->_objectManager->get(Session::class)->setPageData($data);
                $this->_redirect('onitsukatigerindo_rmaaccount/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->_redirect('onitsukatigerindo_rmaaccount/*/');
    }
}
