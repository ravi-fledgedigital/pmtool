<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model;

use Magento\Framework\View\Asset\Repository;

class LogoEncoder
{
    /**
     * @var Repository
     */
    private $assetRepo;

    public function __construct(
        Repository $assetRepo
    ) {
        $this->assetRepo = $assetRepo;
    }
    /**
     * @param string $imgPath
     * @return string
     */
    public function encodeLogoToBase64(string $imgPath): string
    {
        $imageAsset = $this->assetRepo->createAsset($imgPath);
        return 'data:image/' . $imageAsset->getContentType() . ';base64,' . base64_encode($imageAsset->getContent());
    }
}
