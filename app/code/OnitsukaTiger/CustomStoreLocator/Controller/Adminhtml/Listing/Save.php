<?php

namespace OnitsukaTiger\CustomStoreLocator\Controller\Adminhtml\Grid;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use OnitsukaTiger\CustomStoreLocator\Model\GridFactory;
use Magento\Framework\App\RequestInterface;

class Save extends Action
{
    protected $gridFactory;
    protected $request;

    public function __construct(
        Context $context,
        GridFactory $gridFactory,
        RequestInterface $request
    ) {
        parent::__construct($context);
        $this->gridFactory = $gridFactory;
        $this->request = $request;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $this->_redirect('storelocator/grid/addrow');
        }

        // Handle multiple image uploads
        $galleryData = [];
        if (isset($data['gallery']) && is_array($data['gallery'])) {
            foreach ($data['gallery'] as $image) {
                if (isset($image['file'])) {
                    $galleryData[] = $image['file'];
                }
            }
        }

        $mobilegalleryData = [];
        if (isset($data['mobile_gallery']) && is_array($data['mobile_gallery'])) {
            foreach ($data['mobile_gallery'] as $image) {
                if (isset($image['file'])) {
                    $mobilegalleryData[] = $image['file'];
                }
            }
        }
        $data['gallery'] = !empty($galleryData) ? json_encode($galleryData) : null;
        $data['mobile_gallery'] = !empty($mobilegalleryData) ? json_encode($mobilegalleryData) : null;
        $data['stores'] = !empty($data['stores']) ? implode(',', $data['stores']) : 0;

        try {
            if (isset($data['id'])) {
                $rowData = $this->gridFactory->create()->load($data['id']);
            } else {
                $rowData = $this->gridFactory->create();
            }

            $rowData->addData($data);
            $rowData->save();

            $this->messageManager->addSuccessMessage(__('Row Data added successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }

        return $this->_redirect('storelocator/grid/index');
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('OnitsukaTiger_CustomStoreLocator::add_row');
    }
}
