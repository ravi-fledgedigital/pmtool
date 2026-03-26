<?php
namespace Seoulwebdesign\Toast\Controller\Adminhtml\Message;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Seoulwebdesign\Toast\Model\Message;

class Delete extends \Magento\Backend\App\Action
{
    /**
     * Check is allow
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Seoulwebdesign_Toast::message');
    }

    /**
     * Main execute
     *
     * @return Redirect|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('message_id');
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $model = $this->_objectManager->create(Message::class);
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('The message has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['message_id' => $id]);
            }
        }
        $this->messageManager->addError(__('We can\'t find a message to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}
