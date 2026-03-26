<?php

namespace OnitsukaTiger\Cms\Plugin\Controller\Adminhtml\Wysiwyg\Images;

use Magento\Cms\Controller\Adminhtml\Wysiwyg\Images\Thumbnail as Subject;
use Magento\Cms\Helper\Wysiwyg\Images;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\LocalizedException;
use OnitsukaTiger\Cms\Helper\Image as ImageHelper;

class Thumbnail
{
    /**
     * @var Images
     */
    private $wysiwygImages;

    /**
     * @var RawFactory
     */
    private $resultRawFactory;

    /**
     * @var \MagestyApps\WebImages\Helper\ImageHelper
     */
    private $imageHelper;

    /**
     * ThumbnailPlugin constructor.
     * @param Images $wysiwygImages
     * @param RawFactory $resultRawFactory
     * @param ImageHelper $imageHelper
     */
    public function __construct(
        Images $wysiwygImages,
        RawFactory $resultRawFactory,
        ImageHelper $imageHelper
    ) {
        $this->wysiwygImages = $wysiwygImages;
        $this->resultRawFactory = $resultRawFactory;
        $this->imageHelper = $imageHelper;
    }

    /**
     * Handle vector images for media storage thumbnails
     *
     * @param Subject $subject
     * @param callable $proceed
     * @return Raw
     */
    public function aroundExecute(Subject $subject, callable $proceed)
    {
        $file = $subject->getRequest()->getParam('file');
        $file = $this->wysiwygImages->idDecode($file);
        $thumb = $subject->getStorage()->resizeOnTheFly($file);

        if (!$this->imageHelper->isVectorImage($thumb)) {
            return $proceed();
        }

        /** @var Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setHeader('Content-Type', 'image/svg+xml');
        $resultRaw->setContents(file_get_contents($thumb));

        return $resultRaw;
    }
}
