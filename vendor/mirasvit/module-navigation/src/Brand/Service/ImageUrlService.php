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
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Brand\Service;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

use Mirasvit\Brand\Model\Image\ThumbnailFile;

class ImageUrlService
{
    private $assertRepository;

    private $urlBuilder;

    private $storeManager;

    private $thumbnailFile;

    private $mediaResolver;

    public function __construct(
        AssetRepository $assertRepository,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        ThumbnailFile $thumbnailFile,
        BrandMediaResolver $mediaResolver
    ) {
        $this->assertRepository = $assertRepository;
        $this->urlBuilder       = $urlBuilder;
        $this->storeManager     = $storeManager;
        $this->thumbnailFile    = $thumbnailFile;
        $this->mediaResolver    = $mediaResolver;
    }

    public function getImageUrl(string $imageName, ?string $imageType = null): string
    {
        $placeholderUrl = $this->assertRepository->getUrl(
            $this->thumbnailFile->getPlaceholderPath(
                $imageType ?: 'small_image'
            )
        );

        if (empty($imageName)) {
            return $placeholderUrl;
        }

        if ($imageType) {
            if (!$this->thumbnailFile->hasImage($imageType, $imageName)) {
                try {
                    $this->thumbnailFile->createImage($imageType, $imageName);
                } catch (\Exception $e) {
                    return $placeholderUrl;
                }
            }

            $image = (string)$this->thumbnailFile->getImageUrl($imageType, $imageName);
        } else {
            /** @var Store $store */
            $store = $this->storeManager->getStore();
            $basePath = $this->mediaResolver->resolveBasePath($imageName);
            $image = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . "{$basePath}/{$imageName}";
        }

        return $image;
    }
}
