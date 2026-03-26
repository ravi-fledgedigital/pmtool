<?php
namespace OnitsukaTiger\CategoryModelImage\Block\Adminhtml\Product\Helper\Form\Gallery;

use Magento\Framework\App\ObjectManager;
use Magento\Backend\Block\Media\Uploader;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Backend\Block\DataProviders\ImageUploadConfig as ImageUploadConfigDataProvider;
use Magento\MediaStorage\Helper\File\Storage\Database;

/**
 * Block for gallery content.
 */
class Content extends \Magento\Backend\Block\Widget
{
    /**
     * @var string
     */
    protected $_template = 'OnitsukaTiger_CategoryModelImage::catalog/product/helper/gallery.phtml';

    /**
     * @var \Magento\Catalog\Model\Product\Media\Config
     */
    protected $_mediaConfig;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    private $imageHelper;

    /**
     * @var ImageUploadConfigDataProvider
     */
    private $imageUploadConfigDataProvider;

    /**
     * @var Database
     */
    private $fileStorageDatabase;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \OnitsukaTiger\CategoryModelImage\Model\CategoryModelImage\Media\Config $mediaConfig
     * @param array $data
     * @param ImageUploadConfigDataProvider $imageUploadConfigDataProvider
     * @param Database $fileStorageDatabase
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \OnitsukaTiger\CategoryModelImage\Model\CategoryModelImage\Media\Config $mediaConfig,
        array $data = [],
        ImageUploadConfigDataProvider $imageUploadConfigDataProvider = null,
        Database $fileStorageDatabase = null
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        $this->_mediaConfig = $mediaConfig;
        parent::__construct($context, $data);
        $this->imageUploadConfigDataProvider = $imageUploadConfigDataProvider
            ?: ObjectManager::getInstance()->get(ImageUploadConfigDataProvider::class);
        $this->fileStorageDatabase = $fileStorageDatabase
            ?: ObjectManager::getInstance()->get(Database::class);
    }

    /**
     * Prepare layout.
     *
     * @return AbstractBlock
     */
    protected function _prepareLayout()
    {
        $this->addChild(
            'uploader',
            \Magento\Backend\Block\Media\Uploader::class,
            ['image_upload_config_data' => $this->imageUploadConfigDataProvider]
        );

        $this->getUploader()->getConfig()->setUrl(
            $this->_urlBuilder->getUrl('modelimage/product_gallery/upload')
        )->setFileField(
            'image'
        )->setFilters(
            [
                'images' => [
                    'label' => __('Images (.gif, .jpg, .png)'),
                    'files' => ['*.gif', '*.jpg', '*.jpeg', '*.png'],
                ],
            ]
        );
        return parent::_prepareLayout();
    }

    /**
     * Retrieve uploader block
     *
     * @return Uploader
     */
    public function getUploader()
    {
        return $this->getChildBlock('uploader');
    }

    /**
     * Retrieve uploader block html
     *
     * @return string
     */
    public function getUploaderHtml()
    {
        return $this->getChildHtml('uploader');
    }

    /**
     * Returns js object name
     *
     * @return string
     */
    public function getJsObjectName()
    {
        return $this->getHtmlId() . 'JsObject';
    }

    /**
     * @return mixed
     */
    public function getStoreViewId(){
        return $this->getElement()->getDataObject()->getStoreId();
    }
    /**
     * Returns image json
     *
     * @return string
     */
    public function getImagesJson()
    {
        $value = $this->getElement()->getImages();
        if ($value) {
            $mediaDir = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $images = [];
            foreach ($value as &$image) {
                $imagesImage['url'] = $this->_mediaConfig->getMediaUrl($image->getValue());
                $imagesImage['file'] = $this->_mediaConfig->getMediaUrl($image->getValue());
                $imagesImage['positions'] = $image->getPosition();
                $imagesImage['link'] = $image->getLink();
                $imagesImage['value_id'] = $image->getEntityId();
                $imagesImage['entity_id'] = $image->getEntityId();
                $imagesImage['label'] = $image->getAltText();
                $imagesImage['disabled'] = $image->getDisabled();
                if ($this->fileStorageDatabase->checkDbUsage() &&
                    !$mediaDir->isFile($this->_mediaConfig->getMediaPath($image->getValue()))
                ) {
                    $this->fileStorageDatabase->saveFileToFilesystem(
                        $this->_mediaConfig->getMediaPath($image->getValue())
                    );
                }
                try {
                    $fileHandler = $mediaDir->stat($this->_mediaConfig->getMediaPath($image->getValue()));
                    $imagesImage['size'] = $fileHandler['size'];
                } catch (FileSystemException $e) {
                    $imagesImage['url'] = $this->getImageHelper()->getDefaultPlaceholderUrl('small_image');
                    $imagesImage['size'] = 0;
                    $this->_logger->warning($e);
                }
                $images[] = $imagesImage;
            }
            return $this->_jsonEncoder->encode($images);
        }
        return '[]';
    }
    /**
     * Returns image helper object.
     *
     * @return \Magento\Catalog\Helper\Image
     * @deprecated 101.0.3
     */
    private function getImageHelper()
    {
        if ($this->imageHelper === null) {
            $this->imageHelper = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Catalog\Helper\Image::class);
        }
        return $this->imageHelper;
    }
}
