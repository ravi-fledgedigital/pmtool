<?php

namespace OnitsukaTiger\Storelocator\Helper;

use Amasty\Storelocator\Model\ImageProcessor;
use Amasty\Storelocator\Model\ResourceModel\Gallery\Collection;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Helper\AbstractHelper;
/**
 * Class Data
 */
class Data extends AbstractHelper
{
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var Collection
     */
    private $galleryCollection;

    /**
     * @var ImageProcessor
     */
    private $imageProcessor;

    public function __construct(
        StoreManagerInterface $storeManager,
        Collection $galleryCollection,
        ImageProcessor $imageProcessor,
        Context $context
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->galleryCollection = $galleryCollection;
        $this->imageProcessor = $imageProcessor;
    }

    /**
     * @return array
     */
    public function getLocationGallery($locationId)
    {
        $result = [];
        foreach ($this->galleryCollection->getImagesByLocation($locationId) as $image) {
            $result[] = [
                'name' => $image->getData('image_name'),
                'is_base' => (bool)$image->getData('is_base'),
                'path' => $this->imageProcessor->getImageUrl(
                    [ImageProcessor::AMLOCATOR_GALLERY_MEDIA_PATH, $locationId, $image->getData('image_name')]
                )
            ];
        }
        return $result;
    }
}
