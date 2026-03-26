<?php

namespace Seoulwebdesign\Toast\Controller\Adminhtml\Message;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Seoulwebdesign\Toast\Model\Message;

class Save extends \Magento\Backend\App\Action
{
    /**
     * The constructor
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

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
        $data = $this->getRequest()->getPostValue();
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            /** @var Message $model */
            $model = $this->_objectManager->create(Message::class);

            $id = $this->getRequest()->getParam('message_id');
            if ($id) {
                $model->load($id);
            }

            $model->setData($data);
            $jsonData = [];
            foreach ($data as $key => $value) {
                if (substr_count($key, 'var_') && $value) {
                    $jsonData[$key] = $value;
                }
            }
            $model->setData('json_var', json_encode($jsonData));

            $this->_eventManager->dispatch(
                'seoulwebdesign_toast_message_prepare_save',
                ['post' => $model, 'request' => $this->getRequest()]
            );

            try {
                $model->save();
                $this->messageManager->addSuccess(__('You saved this Message.'));
                $this->_objectManager->get(\Magento\Backend\Model\Session::class)->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['message_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the message.'));
            }

            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['message_id' => $this->getRequest()->getParam('message_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}
