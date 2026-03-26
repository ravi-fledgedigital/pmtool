<?php

namespace OnitsukaTiger\Cms\Controller\Adminhtml\Wysiwyg\Images;

class OnInsert extends \Magento\Cms\Controller\Adminhtml\Wysiwyg\Images\OnInsert
{
    /**
     * Fire when select image
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $imagesHelper = $this->_objectManager->get(\Magento\Cms\Helper\Wysiwyg\Images::class);
        $request = $this->getRequest();

        $storeId = $request->getParam('store');

        $filename = $request->getParam('filename');
        $filename = $imagesHelper->idDecode($filename);

        $asIs = $request->getParam('as_is');

        $forceStaticPath = $request->getParam('force_static_path');

        $this->_objectManager->get(\Magento\Catalog\Helper\Data::class)->setStoreId($storeId);
        $imagesHelper->setStoreId($storeId);

        if ($forceStaticPath) {
            $image = $imagesHelper->getCurrentUrl() . $filename;
        } else {
            $image = $imagesHelper->getImageHtmlDeclaration($filename, $asIs);
        }

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        return $resultRaw->setContents($image);
    }
}
