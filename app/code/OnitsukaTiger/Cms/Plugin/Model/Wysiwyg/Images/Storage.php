<?php
namespace OnitsukaTiger\Cms\Plugin\Model\Wysiwyg\Images;

use Magento\Cms\Model\Wysiwyg\Images\Storage as Subject;
use OnitsukaTiger\Cms\Helper\Image as ImageHelper;

class Storage
{
    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * StoragePlugin constructor.
     * @param ImageHelper $imageHelper
     */
    public function __construct(
        ImageHelper $imageHelper
    ) {
        $this->imageHelper = $imageHelper;
    }

    /**
     * Skip resizing vector images
     *
     * @param Subject $subject
     * @param callable $proceed
     * @param $source
     * @param bool $keepRatio
     * @return mixed
     */
    public function aroundResizeFile(Subject $subject, callable $proceed, $source, $keepRatio = true)
    {
        if ($this->imageHelper->isVectorImage($source)) {
            return $source;
        }

        return $proceed($source, $keepRatio);
    }

    /**
     * Return original file path as thumbnail for vector images
     *
     * @param Subject $subject
     * @param callable $proceed
     * @param $filePath
     * @param false $checkFile
     * @return mixed
     */
    public function aroundGetThumbnailPath(Subject $subject, callable $proceed, $filePath, $checkFile = false)
    {
        if ($this->imageHelper->isVectorImage($filePath)) {
            return $filePath;
        }

        return $proceed($filePath, $checkFile);
    }
}
