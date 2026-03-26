<?php


namespace OnitsukaTiger\CategoryModelImage\Model\CategoryModelImage\Media;

use Magento\Framework\Api\Data\ImageContentInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ObjectManager;
use OnitsukaTiger\CategoryModelImage\Model\ResourceModel\CategoryModelImage\Gallery\ImageFactory as ImageResourceFactory;
use OnitsukaTiger\CategoryModelImage\Model\CategoryModelImage\Gallery\ImageFactory;

/**
 * Catalog product Media Gallery attribute processor.
 *
 * @api
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 101.0.0
 */
class Processor
{
    /**
     * @var \Magento\Catalog\Api\Data\ProductAttributeInterface
     * @since 101.0.0
     */
    protected $attribute;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     * @since 101.0.0
     */
    protected $attributeRepository;

    /**
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     * @since 101.0.0
     */
    protected $fileStorageDb;

    /**
     * @var \Magento\Catalog\Model\Product\Media\Config
     * @since 101.0.0
     */
    protected $mediaConfig;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     * @since 101.0.0
     */
    protected $mediaDirectory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Gallery
     * @since 101.0.0
     */
    protected $resourceModel;

    /**
     * @var \Magento\Framework\File\Mime
     */
    private $mime;

    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * @var ImageResourceFactory
     */
    private $imageResourceFactory;

    /**
     * Processor constructor.
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb
     * @param Config $mediaConfig
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Catalog\Model\ResourceModel\Product\Gallery $resourceModel
     * @param \Magento\Framework\File\Mime|null $mime
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb,
        Config $mediaConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Catalog\Model\ResourceModel\Product\Gallery $resourceModel,
        ImageFactory $imageFactory,
        ImageResourceFactory $imageResourceFactory,
        \Magento\Framework\File\Mime $mime = null
    ) {
        $this->imageFactory = $imageFactory;
        $this->imageResourceFactory = $imageResourceFactory;
        $this->attributeRepository = $attributeRepository;
        $this->fileStorageDb = $fileStorageDb;
        $this->mediaConfig = $mediaConfig;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->resourceModel = $resourceModel;
        $this->mime = $mime ?: ObjectManager::getInstance()->get(\Magento\Framework\File\Mime::class);
    }

    public function proccessCategoryGalerry($categoryId,$params,$storeId){
        foreach ($params as $image){
            if($image['removed']){
                $this->removeImage($image['entity_id']);
            }else{
                $status = $image['disabled'] ? 1 : 0;
                if($image['entity_id']){
                    $this->updateImage($image['entity_id'],$categoryId,$image,$status);
                }else{
                    $file = $image['file'];
                    $tmpFilePath = $this->mediaConfig->getTmpMediaShortUrl($file);
                    $fileName = $this->addImage($tmpFilePath);
                    $this->saveNewImage($categoryId,$fileName,$image,$status,$storeId);
                }
            }
        }
    }

    /**
     * @param $file
     * @param bool $move
     * @return string|string[]
     * @throws LocalizedException
     */
    public function addImage(
        $file,
        $move = false
    ) {
        $file = $this->mediaDirectory->getRelativePath($file);
        if (!$this->mediaDirectory->isFile($file)) {
            throw new LocalizedException(__("The image doesn't exist."));
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $pathinfo = pathinfo($file);
        $imgExtensions = ['jpg', 'jpeg', 'gif', 'png'];
        if (!isset($pathinfo['extension']) || !in_array(strtolower($pathinfo['extension']), $imgExtensions)) {
            throw new LocalizedException(
                __('The image type for the file is invalid. Enter the correct image type and try again.')
            );
        }

        $fileName = \Magento\MediaStorage\Model\File\Uploader::getCorrectFileName($pathinfo['basename']);
        $dispersionPath = \Magento\MediaStorage\Model\File\Uploader::getDispersionPath($fileName);
        $fileName = $dispersionPath . '/' . $fileName;

        $fileName = $this->getNotDuplicatedFilename($fileName, $dispersionPath);

        $destinationFile = $this->mediaConfig->getMediaShortUrl($fileName);

        try {
            /** @var $storageHelper \Magento\MediaStorage\Helper\File\Storage\Database */
            $storageHelper = $this->fileStorageDb;
            if ($move) {
                $this->mediaDirectory->renameFile($file, $destinationFile);

                //If this is used, filesystem should be configured properly
                $storageHelper->saveFile($this->mediaConfig->getTmpMediaShortUrl($fileName));
            } else {
                $this->mediaDirectory->copyFile($file, $destinationFile);

                $storageHelper->saveFile($this->mediaConfig->getTmpMediaShortUrl($fileName));
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('The "%1" file couldn\'t be moved.', $e->getMessage()));
        }

        $fileName = str_replace('\\', '/', $fileName);

        return $fileName;
    }

    /**
     * Get filename which is not duplicated with other files in media temporary and media directories
     *
     * @param string $fileName
     * @param string $dispersionPath
     * @return string
     * @since 101.0.0
     */
    protected function getNotDuplicatedFilename($fileName, $dispersionPath)
    {
        $fileMediaName = $dispersionPath . '/'
            . \Magento\MediaStorage\Model\File\Uploader::getNewFileName($this->mediaConfig->getMediaPath($fileName));
        $fileTmpMediaName = $dispersionPath . '/'
            . \Magento\MediaStorage\Model\File\Uploader::getNewFileName($this->mediaConfig->getTmpMediaPath($fileName));

        if ($fileMediaName != $fileTmpMediaName) {
            if ($fileMediaName != $fileName) {
                return $this->getNotDuplicatedFilename(
                    $fileMediaName,
                    $dispersionPath
                );
            } elseif ($fileTmpMediaName != $fileName) {
                return $this->getNotDuplicatedFilename(
                    $fileTmpMediaName,
                    $dispersionPath
                );
            }
        }

        return $fileMediaName;
    }

    /**
     * @param $categoryId
     * @param $fileName
     * @param $image
     * @param $status
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function saveNewImage($categoryId, $fileName, $image, $status,$storeId){
        $categoryGallerry = $this->imageFactory->create();
        $categoryGallerry->setCategoryId($categoryId);
        $categoryGallerry->setValue($fileName);
        $categoryGallerry->setPosition($image['positions']);
        $categoryGallerry->setLink($image['link']);
        $categoryGallerry->setAltText($image['label']);
        if($storeId){
            $categoryGallerry->setStore($storeId);
        }
        $categoryGallerry->setDisabled($status);
        $this->imageResourceFactory->create()
            ->save($categoryGallerry);
    }

    /**
     * @param $entityId
     * @param $categoryId
     * @param $image
     * @param $status
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function updateImage($entityId, $categoryId, $image, $status){
        $categoryGallerry = $this->imageFactory->create()->load($entityId);
        $categoryGallerry->setPosition($image['positions']);
        $categoryGallerry->setLink($image['link']);
        $categoryGallerry->setAltText($image['label']);
        $categoryGallerry->setDisabled($status);
        $this->imageResourceFactory->create()
            ->save($categoryGallerry);
    }

    /**
     * @param $entityId
     * @throws \Exception
     */
    public function removeImage($entityId){
        if($entityId){
            $categoryGallerry = $this->imageFactory->create()->load($entityId);
            if($categoryGallerry){
                $this->imageResourceFactory->create()
                    ->delete($categoryGallerry);
            }
        }
    }
}
