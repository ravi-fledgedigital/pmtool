<?php

namespace OnitsukaTiger\Catalog\Model\Category\Attribute\Backend;

use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\File\Uploader;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend as CategoryAbstractBackend;

class Image extends \Magento\Catalog\Model\Category\Attribute\Backend\Image
{

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ImageUploader
     */
    private $imageUploader;

    /**
     * @var string
     */
    private $additionalData = '_additional_data_';

    /**
     * Image constructor.
     * @param LoggerInterface $logger
     * @param Filesystem $filesystem
     * @param UploaderFactory $fileUploaderFactory
     * @param StoreManagerInterface|null $storeManager
     * @param ImageUploader|null $imageUploader
     */
    public function __construct(
        LoggerInterface $logger,
        Filesystem $filesystem,
        UploaderFactory $fileUploaderFactory,
        StoreManagerInterface $storeManager = null,
        ImageUploader $imageUploader = null
    ) {
        parent::__construct($logger, $filesystem, $fileUploaderFactory, $storeManager, $imageUploader);
        $this->storeManager = $storeManager ??
            ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->imageUploader = $imageUploader ??
            ObjectManager::getInstance()->get(ImageUploader::class);
    }


    /**
     * Avoiding saving potential upload data to DB.
     *
     * Will set empty image attribute value if image was not uploaded.
     *
     * @param \Magento\Framework\DataObject $object
     * @return \Magento\Catalog\Model\Category\Attribute\Backend\Image
     * @throws \Magento\Framework\Exception\FileSystemException
     * @since 101.0.8
     */
    public function beforeSave($object)
    {
        $attributeName = $this->getAttribute()->getName();
        $value = $object->getData($attributeName);

        if ($this->isTmpFileAvailable($value) && $imageName = $this->getUploadedImageName($value)) {
            try {
                /** @var StoreInterface $store */
                $store = $this->storeManager->getStore();
                $baseMediaDir = $store->getBaseMediaDir();
                $newImgRelativePath = $this->imageUploader->moveFileFromTmp($imageName, true);
                $value[0]['url'] = $baseMediaDir . $newImgRelativePath;
                $value[0]['name'] = basename($newImgRelativePath);
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        } elseif ($this->fileResidesOutsideCategoryDir($value)) {
            // use relative path for image attribute so we know it's outside of category dir when we fetch it
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $value[0]['url'] = parse_url($value[0]['url'], PHP_URL_PATH);
            $value[0]['name'] = basename($value[0]['url']);
        }

        if ($imageName = $this->getUploadedImageName($value)) {
            $object->setData($this->additionalData . $attributeName, $value);
            $object->setData($attributeName, $imageName);
        } elseif (!is_string($value)) {
            $object->setData($attributeName, null);
        }

        return CategoryAbstractBackend::beforeSave($object);
    }

    /**
     * Check if temporary file is available for new image upload.
     *
     * @param array $value
     * @return bool
     */
    private function isTmpFileAvailable($value)
    {
        return is_array($value) && isset($value[0]['tmp_name']);
    }

    /**
     * Check for file path resides outside of category media dir. The URL will be a path including pub/media if true
     *
     * @param array|null $value
     * @return bool
     */
    private function fileResidesOutsideCategoryDir($value)
    {
        if (!is_array($value) || !isset($value[0]['url'])) {
            return false;
        }

        $fileUrl = ltrim($value[0]['url'], '/');
        $baseMediaDir = $this->_filesystem->getUri(DirectoryList::MEDIA);

        if (!$baseMediaDir) {
            return false;
        }

        return strpos($fileUrl, $baseMediaDir) !== false;
    }

    /**
     * Gets image name from $value array.
     *
     * Will return empty string in a case when $value is not an array.
     *
     * @param array $value Attribute value
     * @return string
     */
    private function getUploadedImageName($value)
    {
        if (is_array($value) && isset($value[0]['name'])) {
            return $value[0]['name'];
        }

        return '';
    }

    /**
     * Check that image name exists in catalog/category directory and return new image name if it already exists.
     *
     * @param string $imageName
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function checkUniqueImageName(string $imageName): string
    {
        $mediaDirectory = $this->_filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $imageAbsolutePath = $mediaDirectory->getAbsolutePath(
            $this->imageUploader->getBasePath() . DIRECTORY_SEPARATOR . $imageName
        );

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $imageName = call_user_func([Uploader::class, 'getNewFilename'], $imageAbsolutePath);

        return $imageName;
    }
}
