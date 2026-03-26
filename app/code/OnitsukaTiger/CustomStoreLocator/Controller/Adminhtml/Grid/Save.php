<?php

namespace OnitsukaTiger\CustomStoreLocator\Controller\Adminhtml\Grid;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use OnitsukaTiger\CustomStoreLocator\Model\GridFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\App\Filesystem\DirectoryList;

class Save extends Action
{
    protected $gridFactory;
    protected $request;
    protected $fileIo;
    protected $mediaDirectory;

    public function __construct(
        Context $context,
        GridFactory $gridFactory,
        RequestInterface $request,
        File $fileIo,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->gridFactory = $gridFactory;
        $this->request = $request;
        $this->fileIo = $fileIo;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $this->_redirect('storelocator/grid/addrow');
        }

        try {
            if (isset($data['id'])) {
                $rowData = $this->gridFactory->create()->load($data['id']);
                $currentStoreId = $rowData->getId();
            } else {
                $rowData = $this->gridFactory->create();
                $currentStoreId = null;
            }


            $positionToCheck = $data['position'] ?? null;
            if ($positionToCheck !== null) {
                $collection = $this->gridFactory->create()->getCollection()
                    ->addFieldToFilter('position', $positionToCheck);

                if ($currentStoreId) {
                    $collection->addFieldToFilter('id', ['neq' => $currentStoreId]);
                }

                if ($collection->getSize() > 0) {
                    $this->messageManager->addErrorMessage(__('The position value "%1" is already used. Please use a unique value.', $positionToCheck));
                    return $this->_redirect('storelocator/grid/addrow');
                }
            }

            $finalImages = [];

            if (isset($data['gallery']) && is_array($data['gallery'])) {
                foreach ($data['gallery'] as $image) {
                    if (!empty($image['name']) && empty($image['delete'])) {
                        $finalImages[] = $image['name'];
                    }

                    if (!empty($image['delete']) && !empty($image['name'])) {
                        $filePath = $this->mediaDirectory->getAbsolutePath('gallery/image/' . $image['name']);
                        if ($this->fileIo->fileExists($filePath)) {
                            $this->fileIo->rm($filePath);
                        }
                    }
                }
            }

            $finalMobileImages = [];

            if (isset($data['mobile_gallery']) && is_array($data['mobile_gallery'])) {
                foreach ($data['mobile_gallery'] as $image) {
                    if (!empty($image['name']) && empty($image['delete'])) {
                        $finalMobileImages[] = $image['name'];
                    }

                    if (!empty($image['delete']) && !empty($image['name'])) {
                        $filePath = $this->mediaDirectory->getAbsolutePath('mobile_gallery/image/' . $image['name']);
                        if ($this->fileIo->fileExists($filePath)) {
                            $this->fileIo->rm($filePath);
                        }
                    }
                }
            }

            $data['gallery'] = !empty($finalImages) ? json_encode($finalImages) : null;
            $data['mobile_gallery'] = !empty($finalMobileImages) ? json_encode($finalMobileImages) : null;
            $data['stores'] = !empty($data['stores']) ? implode(',', $data['stores']) : 0;

            $rowData->addData($data);
            $rowData->save();

            $this->messageManager->addSuccessMessage(__('Store saved successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error: ') . $e->getMessage());
        }

        return $this->_redirect('storelocator/grid/index');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('OnitsukaTiger_CustomStoreLocator::add_row');
    }
}
