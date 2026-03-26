<?php

namespace OnitsukaTigerIndo\SizeConverter\Controller\Adminhtml\Index;

use Magento\Backend\Model\View\Result\Redirect;

class Save extends \Magento\Backend\App\Action
{
    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $indoSizeFactory = $this->_objectManager->create('OnitsukaTigerIndo\SizeConverter\Model\IndoSizeFactory');

            $englishSize = $this->getRequest()->getParam('english_size');
            $gender = $this->getRequest()->getParam('gender');
            $storeIds = $this->getRequest()->getParam('store_ids');

            $collection = $indoSizeFactory->create()->getCollection();
            $collection->addFieldToFilter('english_size', ['eq' => $englishSize]);
            $collection->addFieldToFilter('gender', ['eq' => $gender]);
            $collection->addFieldToFilter('store_ids', implode(',', $storeIds));

            $data["store_ids"]= !empty($storeIds) ? implode(',', $storeIds) : 0;
            if ($collection && $collection->getSize() > 0) {
                $this->messageManager->addErrorMessage(__('The record already exist with same English Size and Gender and Store.'));
                return $resultRedirect->setPath('*/*/');
            } else {
                $model = $this->_objectManager->create('OnitsukaTigerIndo\SizeConverter\Model\IndoSize');

                $id = $this->getRequest()->getParam('size_id');
                if ($id) {
                    $model->load($id);
                }

                $model->setData($data);

                try {
                    $model->save();
                    $this->messageManager->addSuccess(__('You saved the size.'));
                    $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                    if ($this->getRequest()->getParam('back')) {
                        return $resultRedirect->setPath('*/*/edit', ['size_id' => $model->getId(), '_current' => true]);
                    }
                    return $resultRedirect->setPath('*/*/');
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->messageManager->addError($e->getMessage());
                } catch (\RuntimeException $e) {
                    $this->messageManager->addError($e->getMessage());
                } catch (\Exception $e) {
                    $this->messageManager->addException($e, __('Something went wrong while saving the size.'));
                }
                $this->_getSession()->setFormData($data);
                return $resultRedirect->setPath('*/*/edit', ['size_id' => $this->getRequest()->getParam('size_id')]);
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
