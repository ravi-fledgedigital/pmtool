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


namespace Mirasvit\LayeredNavigation\Model\Config;


use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\LayeredNavigation\Model\ConfigProvider;

class FileProcessor
{
    private $uploaderFactory;

    private $mediaDirectory;

    private $storeManager;

    public function __construct(
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager
    ) {
        $this->uploaderFactory = $uploaderFactory;
        $this->storeManager    = $storeManager;
        $this->mediaDirectory  = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    public function save(string $fileId): array
    {
        try {
            $result         = $this->saveFile($fileId, $this->getAbsoluteMediaPath());
            $result['name'] = $result['file'];
            $result['url']  = $this->getMediaUrl($result['file']);
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        return $result;
    }

    /**
     * @throws LocalizedException
     */
    private function saveFile(string $fileId, string $destination): array
    {
        $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(false);

        return $uploader->save($destination);
    }

    /**
     * Retrieve absolute temp media path
     */
    private function getAbsoluteMediaPath(): string
    {
        return $this->mediaDirectory->getAbsolutePath(ConfigProvider::MEDIA_FOLDER);
    }

    private function getMediaUrl(string $file): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
            . ConfigProvider::MEDIA_FOLDER . '/' . $file;
    }
}