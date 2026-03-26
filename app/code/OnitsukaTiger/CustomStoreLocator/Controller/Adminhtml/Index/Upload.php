<?php

namespace OnitsukaTiger\CustomStoreLocator\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class Upload extends \Magento\Backend\App\Action
{
    protected $imageUploader;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Catalog\Model\ImageUploader $imageUploader
    ) {
        parent::__construct($context);
        $this->imageUploader = $imageUploader;
    }

    public function execute()
    {
        $imageUploadId = $this->getRequest()->getParam('param_name', 'gallery');

        try {
            $imageResult = $this->imageUploader->saveFileToTmpDir($imageUploadId);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'Disallowed file type') !== false) {
                $msg = 'Only JPG, JPEG, PNG, and GIF files are allowed.';
            }
            $imageResult = ['error' => $msg, 'errorcode' => $e->getCode()];
        } catch (\Exception $e) {
            $imageResult = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($imageResult);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('OnitsukaTiger_CustomStoreLocator::add_row');
    }
}
