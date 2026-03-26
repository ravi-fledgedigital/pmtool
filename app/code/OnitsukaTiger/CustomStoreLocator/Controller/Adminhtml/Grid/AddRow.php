<?php

namespace OnitsukaTiger\CustomStoreLocator\Controller\Adminhtml\Grid;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use OnitsukaTiger\CustomStoreLocator\Model\GridFactory;
use Magento\Store\Model\StoreManagerInterface;

class AddRow extends Action
{
    private $coreRegistry;
    private $gridFactory;
    private $storeManager;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        GridFactory $gridFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->gridFactory = $gridFactory;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        $rowId = (int)$this->getRequest()->getParam('id');
        $rowData = $this->gridFactory->create();

        if ($rowId) {
            $rowData = $rowData->load($rowId);
            $rowName = $rowData['store_name'];

            if (!$rowData['id']) {
                $this->messageManager->addError(__('Row data is no longer available'));
                return $this->_redirect('storelocator/grid/index');
            }

            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            $galleryData = json_decode($rowData->getData('gallery'), true);
            $mobileGalleryData = json_decode($rowData->getData('mobile_gallery'), true);

            if (is_array($galleryData)) {
                $images = [];
                foreach ($galleryData as $img) {
                    $images[] = [
                        'name' => $img,
                        'url' => $mediaUrl . 'gallery/image/' . $img
                    ];
                }
                $rowData->setData('gallery', $images);
            } elseif (is_string($galleryData)) {
                $rowData->setData('gallery', [[
                    'name' => $galleryData,
                    'url' => $mediaUrl . 'gallery/image/' . $galleryData
                ]]);
            }

            if (is_array($mobileGalleryData)) {
                $images = [];
                foreach ($mobileGalleryData as $img) {
                    $images[] = [
                        'name' => $img,
                        'url' => $mediaUrl . 'mobile_gallery/image/' . $img
                    ];
                }
                $rowData->setData('mobile_gallery', $images);
            } elseif (is_string($mobileGalleryData)) {
                $rowData->setData('mobile_gallery', [[
                    'name' => $mobileGalleryData,
                    'url' => $mediaUrl . 'mobile_gallery/image/' . $mobileGalleryData
                ]]);
            }
        }

        $this->coreRegistry->register('row_data', $rowData);
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $title = $rowId ? __('Edit Store') : __('Add New Store');

        $resultPage->getConfig()->getTitle()->prepend($title);
        return $resultPage;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('OnitsukaTiger_CustomStoreLocator::add_row');
    }
}
