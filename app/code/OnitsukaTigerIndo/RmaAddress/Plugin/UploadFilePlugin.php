<?php
/** phpcs:ignoreFile */

namespace OnitsukaTigerIndo\RmaAddress\Plugin;

use Amasty\Rma\Model\ConfigProvider;
use Magento\Backend\Model\Session;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Math\Random;
use Magento\Framework\UrlInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class UploadFilePlugin extends \Amasty\Rma\Utils\FileUpload
{
    public const MEDIA_PATH = 'amasty/rma/';
    public const OLD_RMA_MEDIA_PATH = 'amasty/rma/uploads/';
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'gif', 'png', 'pdf', 'mp4'];
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

    /**
     * @var Filesystem\DirectoryList
     */
    protected $directoryList;


    /**
     * @param ConfigProvider $configProvider
     * @param Filesystem $filesystem
     * @param UploaderFactory $fileUploaderFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param UrlInterface $url
     * @param Session $session
     * @param Random $mathRandom
     */
    public function __construct(
        ConfigProvider $configProvider,
        Filesystem $filesystem,
        UploaderFactory $fileUploaderFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        UrlInterface $url,
        Session $session,
        Random $mathRandom,
        Filesystem\DirectoryList $directoryList
    ) {
        $this->filesystem = $filesystem;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->configProvider = $configProvider;
        $this->url = $url;
        $this->session = $session;
        $this->mathRandom = $mathRandom;
        $this->directoryList = $directoryList;
    }

    /**
     * Get Rma Tmp Path
     *
     * @return string
     */
    private function getRmaTempPath()
    {
        if ($this->filesystem !== null) {
            return $this->filesystem->getDirectoryRead(
                DirectoryList::MEDIA
            )->getAbsolutePath(
                self::MEDIA_PATH . 'temp/'
            );
        } else {
            throw new \Exception('$filesystem is null');
        }
    }

    /**
     * Get Old Media Path
     *
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
     * Delete File Method
     *
     * @param $fileHash
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function deleteTemp($fileHash)
    {
        $rootPath  =  $this->directoryList->getPath('media');
        $filePath = $rootPath . '/' . self::MEDIA_PATH . 'temp/';
        if (file_exists($filePath) && is_file($filePath)) {
            unlink($filePath);
        } else {
            $this->logger->info("RMA attachment delete File not found : . $fileHash . or invalid file path: . $filePath .");
        }
    }

    /**
     *  Upload File Method
     *
     * @param array $files
     * @param string $maxFileSize
     * @return array[]
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
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
     * Save File Function
     *
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
     * Prepare Message Files
     *
     * @param \Amasty\Rma\Api\Data\MessageFileInterface[] $messageFiles
     * @param int $requestId
     * @param bool $isAdmin
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
     * Validate File
     *
     * @param string $filename
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
}
