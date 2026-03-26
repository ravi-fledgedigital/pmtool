<?php

namespace OnitsukaTiger\EmailToWareHouse\Helper;


use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\Asset\Repository;
/**
 * Class Data
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $assetRepository;

    public function __construct(Context $context,Repository $assetRepository)
    {
        $this->assetRepository = $assetRepository;
        parent::__construct($context);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMediaFromUrl($path = null){

        if (empty($path)){
            return $path;
        }
        $mediaAsset = $this->assetRepository->createAsset($path);
        return "data:image/png;base64,". base64_encode(file_get_contents($mediaAsset->getSourceFile()));

    }
}
