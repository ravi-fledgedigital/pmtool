<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Utils;

use Amasty\Rma\Model\ConfigProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Math\Random;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;
use Magento\Backend\Model\Session;

class FileUpload
{
    public const MEDIA_PATH = 'amasty/rma/';

    public const OLD_RMA_MEDIA_PATH = 'amasty/rma/uploads/';

    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'gif', 'png', 'pdf'];

    public const FILEHASH = 'filehash';
    public const FILENAME = 'filename';
    public const EXTENSION = 'extension';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var UploaderFactory
     */
    private $fileUploaderFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Random
     */
    private $mathRandom;

    public function __construct(
        ConfigProvider $configProvider,
        Filesystem $filesystem,
        UploaderFactory $fileUploaderFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        UrlInterface $url,
        Session $session,
        Random $mathRandom
    ) {
        $this->filesystem = $filesystem;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->configProvider = $configProvider;
        $this->url = $url;
        $this->session = $session;
        $this->mathRandom = $mathRandom;
    }

    /**
     * @return string
     */
    private function getRmaTempPath()
    {
        return $this->filesystem->getDirectoryRead(
            DirectoryList::MEDIA
        )->getAbsolutePath(
            self::MEDIA_PATH . 'temp/'
        );
    }

    /**
     * @return string
     */
    private function getOldRmaMediaPath()
    {
        return $this->filesystem->getDirectoryRead(
            DirectoryList::MEDIA
        )->getAbsolutePath(
            self::OLD_RMA_MEDIA_PATH
        );
    }

    /**
     * @param string $fileHash
     * @param string $extension
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function deleteTemp($fileHash)
    {
        $path = $this->getRmaTempPath();
        $writer = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $path .= $fileHash;

        if ($writer->isExist($path)) {
            $writer->delete($path);
        }
    }

    /**
     * @param int $requestId
     * @param string $fileHash
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function deleteMessageFile($requestId, $fileHash)
    {
        $writer = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $path = $writer->getRelativePath(self::MEDIA_PATH) . (int)$requestId . DIRECTORY_SEPARATOR . $fileHash;

        if ($writer->isExist($path)) {
            $writer->delete($path);
        }
    }

    /**
     * @param array $files
     * @param string $maxFileSize
     *
     * @return array
     * @throws \Exception
     */
    public function uploadFile($files, $maxFileSize)
    {
        $path = $this->getRmaTempPath();
        $writer = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $writer->create($path);

        $result = [];
        $errors = [];

        foreach ($files as $name => $file) {
            if ($maxFileSize > 0 && ($file['size'] > $maxFileSize * 1024)) {
                $errors[] = $file['name'];
                continue;
            }
            //phpcs:ignore
            $extension = mb_strtolower('.' . pathinfo($file['name'], PATHINFO_EXTENSION));

            $fileHash = $this->mathRandom->getUniqueHash() . $extension;

            if ($writer->isExist($path . $fileHash)) {
                $this->deleteTemp($fileHash);
            }

            try {
                /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
                $uploader = $this->fileUploaderFactory->create(
                    ['fileId' => (string)$name]
                );
                $uploader->setAllowedExtensions(self::ALLOWED_EXTENSIONS);
                $uploader->setAllowRenameFiles(true);
                $uploader->save($path, $fileHash);

                $result[] = [
                    self::FILEHASH => $fileHash,
                    self::FILENAME => (string)$name,
                    self::EXTENSION => $extension
                ];
            } catch (\Exception $e) {
                if ($e->getCode() != \Magento\MediaStorage\Model\File\Uploader::TMP_NAME_EMPTY) {
                    $this->logger->critical($e);
                }
            }
        }

        return [$result, $errors];
    }

    /**
     * @param array $file
     * @param int $requestId
     *
     * @return array
     */
    public function uploadShippingLabel($file, $requestId)
    {
        try {
            $path = $this->filesystem->getDirectoryRead(
                DirectoryList::MEDIA
            )->getAbsolutePath(
                self::MEDIA_PATH . $requestId
            );
            $uploader = $this->fileUploaderFactory->create(['fileId' => 'shipping_label']);
            $uploader->setAllowedExtensions(self::ALLOWED_EXTENSIONS);
            $uploader->setAllowRenameFiles(true);

            //phpcs:ignore
            $extension = '.' . pathinfo($file['name'], PATHINFO_EXTENSION);

            $newName = $this->mathRandom->getUniqueHash();

            $result = $uploader->save($path, $newName . $extension);
            unset($result['path']);

            if (!$result) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('File can not be saved to the destination folder.')
                );
            }
            $result['tmp_name'] = str_replace('\\', '/', $result['tmp_name']);
            $result['url'] = $this->storeManager->getStore()->getBaseUrl('media')
                . self::MEDIA_PATH . $requestId . DIRECTORY_SEPARATOR . $result['file'];
            $result['name'] = $result['file'];
            $result['cookie'] = [
                'name' => $this->session->getName(),
                'value' => $this->session->getSessionId(),
                'lifetime' => $this->session->getCookieLifetime(),
                'path' => $this->session->getCookiePath(),
                'domain' => $this->session->getCookieDomain(),
            ];
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        return $result;
    }

    /**
     * @param string $name
     * @param int $requestId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function deleteShippingLabel($name, $requestId)
    {
        $path = $this->filesystem->getDirectoryRead(
            DirectoryList::MEDIA
        )->getAbsolutePath(
            self::MEDIA_PATH . $requestId . DIRECTORY_SEPARATOR
        );
        $writer = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $path .= $name;

        if ($writer->isExist($path)) {
            $writer->delete($path);
            return true;
        }

        return false;
    }

    /**
     * @param string $name
     * @param int $requestId
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getLabelUrl($name, $requestId)
    {
        return $this->storeManager->getStore()->getBaseUrl('media')
        . self::MEDIA_PATH . $requestId . DIRECTORY_SEPARATOR . $name;
    }

    /**
     * @param \Amasty\Rma\Api\Data\MessageFileInterface[] $files
     * @param int $requestId
     *
     * @throws \Exception
     */
    public function saveFiles($files, $requestId)
    {
        $path = $this->filesystem->getDirectoryRead(
            DirectoryList::MEDIA
        )->getAbsolutePath(
            self::MEDIA_PATH
        );
        $writer = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

        foreach ($files as $file) {
            if (!$this->validateFile($file->getFilepath())
            ) {
                continue;
            }

            $filePath = $path . 'temp/' . $file->getFilepath();
            $requestPath = $path . $requestId . DIRECTORY_SEPARATOR;

            $writer->create($requestPath);
            $resultPath = $requestPath . $file->getFilepath();

            if ($writer->isExist($filePath)) {
                $writer->copyFile($filePath, $resultPath);
                $writer->delete($filePath);
                $file->setUrlHash($this->mathRandom->getUniqueHash());
            }
        }
    }

    /**
     * @param \Amasty\Rma\Api\Data\MessageFileInterface[] $messageFiles
     * @param int $requestId
     *
     * @param bool $isAdmin
     *
     * @return array
     */
    public function prepareMessageFiles($messageFiles, $requestId, $isAdmin = false)
    {
        $result = [];

        foreach ($messageFiles as $messageFile) {
            if ($isAdmin) {
                $link = $this->url->getUrl(
                    'amrma/chat/download',
                    ['hash' => $messageFile->getUrlHash(), 'request_id' => $requestId]
                );
            } else {
                $link = $this->url->getUrl(
                    $this->configProvider->getUrlPrefix() . '/chat/download',
                    ['hash' => $messageFile->getUrlHash(), 'request_id' => $requestId]
                );
            }
            $result[] = [
                'filename' => $messageFile->getFilename(),
                'link' => $link
            ];
        }

        return $result;
    }

    /**
     * @param string $filename
     * @param string $extension
     *
     * @return bool
     */
    private function validateFile($filename)
    {
        //phpcs:ignore
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if (!in_array(ltrim($extension, '.'), self::ALLOWED_EXTENSIONS)) {
            return false;
        }

        if (!preg_match('/^[a-z0-9]{32}$/i', str_replace('.' . $extension, '', $filename))) {
            return false;
        }

        return true;
    }

    /**
     * @param string $fileName
     *
     * @return string|bool
     */
    public function moveFileToTmp($fileName)
    {
        $fileHash = $this->mathRandom->getUniqueHash();
        //phpcs:ignore
        $extension = '.' . pathinfo($fileName, PATHINFO_EXTENSION);

        $writer = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $writer->create($this->getRmaTempPath());

        $filePath = $this->getOldRmaMediaPath() . $fileName;

        if ($this->oldFileIsExists($filePath)) {
            return false;
        }

        $destinationPath = $this->getRmaTempPath() . $fileHash . $extension;

        try {
            $writer->copyFile($filePath, $destinationPath);
        } catch (LocalizedException $e) {
            return false;
        }

        return $fileHash . $extension;
    }

    /**
     * @param string $fileName
     *
     * @return bool
     */
    private function oldFileIsExists($fileName)
    {
        $writer = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $oldFilePath = $this->getOldRmaMediaPath() . $fileName;

        return $writer->isExist($oldFilePath);
    }
}
