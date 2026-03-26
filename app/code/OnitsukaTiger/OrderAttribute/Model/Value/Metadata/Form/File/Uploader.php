<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form\File;

use OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form\File\Uploader\Validator;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Uploader as FrameworkUploader;
use Magento\Framework\File\UploaderFactory as FrameworkUploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class Uploader
{
    /**
     * @var WriteInterface
     */
    private $mediaDirectory;

    /**
     * @var FrameworkUploaderFactory
     */
    private $uploaderFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var string
     */
    private $basePath;

    public function __construct(
        Filesystem $filesystem,
        FrameworkUploaderFactory $uploaderFactory,
        StoreManagerInterface $storeManager,
        Validator $validator,
        string $basePath = 'onitsukatiger_checkout'
    ) {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->uploaderFactory = $uploaderFactory;
        $this->storeManager = $storeManager;
        $this->validator = $validator;
        $this->basePath = $basePath;
    }

    /**
     * Checking file for save and save it
     *
     * @param string $fileId
     * @return string[]
     *
     * @throws LocalizedException
     */
    public function saveFile(string $fileId): array
    {
        if ($this->validator->validateAttributeCode($fileId)) {

            /** @var FrameworkUploader $uploader */
            $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
            $uploader->setFilesDispersion(true);
            $uploader->setFilenamesCaseSensitivity(false);
            $uploader->setAllowRenameFiles(true);
            $result = $uploader->save($this->mediaDirectory->getAbsolutePath($this->basePath));
            $fileName = $uploader->getUploadedFileName();
            $result['url'] = $this->storeManager
                ->getStore()
                ->getBaseUrl(
                    UrlInterface::URL_TYPE_MEDIA
                ) . $this->getFilePath($this->basePath, $fileName);
            unset($result['tmp_name']);

            if (!$this->validator->validateFile($fileId, $result)) {
                throw new LocalizedException(
                    __('File can not be saved to the destination folder.')
                );
            }

            return $result;
        }

        throw new LocalizedException(__('Something went wrong while saving the file(s).'));
    }

    /**
     * Retrieve file info
     *
     * @param $fileName
     * @return array
     */
    public function getFileInfo(string $fileName): array
    {
        // phpcs:disable Magento2.Functions.DiscouragedFunction
        $filePath = $this->getFilePath($this->basePath, $fileName);
        $fileInfo = [];

        if ($this->mediaDirectory->isFile($filePath)) {
            $stat = $this->mediaDirectory->stat($filePath);
            $fileInfo = [
                'name' => basename($fileName),
                'file_path' => $filePath,
                'file' => $fileName,
                'size' => $stat['size'] ?? 0,
                'url' => $this->storeManager
                    ->getStore()
                    ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $filePath
            ];
        }

        return $fileInfo;
    }

    /**
     * Retrieve path
     *
     * @param string $path
     * @param string $fileName
     *
     * @return string
     */
    private function getFilePath(string $path, string $fileName): string
    {
        return rtrim($path, '/') . '/' . ltrim($fileName, '/');
    }
}
