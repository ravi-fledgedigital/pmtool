<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Service;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Store\Model\StoreManagerInterface;

class ImageUrlService
{
    private $storeManager;

    private $assertRepository;

    public function __construct(
        AssetRepository       $assertRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->assertRepository = $assertRepository;
        $this->storeManager     = $storeManager;
    }

    public function getImageUrl(?string $imageName): string
    {
        $placeholderUrl = $this->assertRepository->getUrl($this->getPlaceholderPath('small_image'));

        if (empty($imageName)) {
            return $placeholderUrl;
        }

        $store = $this->storeManager->getStore();
        $image = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . "landing/{$imageName}";

        return $image;
    }

    public function getPlaceholderPath($imageType): string
    {
        return "Magento_Catalog::images/product/placeholder/{$imageType}.jpg";
    }
}
