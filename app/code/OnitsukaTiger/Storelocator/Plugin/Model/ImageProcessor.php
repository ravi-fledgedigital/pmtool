<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */

namespace OnitsukaTiger\Storelocator\Plugin\Model;

class ImageProcessor
{
    /**
     * @var \Magento\Framework\ImageFactory
     */
    private $imageFactory;

    /**
     * Marker image type
     */
    const MARKER_IMAGE_TYPE = 'marker_img';

    public function __construct(
        \Magento\Framework\ImageFactory $imageFactory,
        \Magento\Framework\View\Element\Template\Context $context
    ) {
        $this->imageFactory = $imageFactory;
    }

    /**
     * @param \Amasty\Storelocator\Model\ImageProcessor $subject
     * @param callable $proceed
     * @param $filename
     * @param $imageType
     * @param bool $needResize
     */
    public function aroundPrepareImage(\Amasty\Storelocator\Model\ImageProcessor $subject, callable $proceed, $filename, $imageType, $needResize = false)
    {
        /** @var \Magento\Framework\Image $imageProcessor */
        $imageProcessor = $this->imageFactory->create(['fileName' => $filename]);
        $imageProcessor->keepAspectRatio(true);
        $imageProcessor->keepFrame(true);
        $imageProcessor->keepTransparency(true);
        if ($imageType == self::MARKER_IMAGE_TYPE || $needResize) {
            $imageProcessor->resize(80, 107);
        }
        $imageProcessor->save();
    }
}
