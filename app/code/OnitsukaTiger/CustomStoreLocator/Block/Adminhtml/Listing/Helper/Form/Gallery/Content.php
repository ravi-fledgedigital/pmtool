<?php
namespace OnitsukaTiger\CustomStoreLocator\Block\Adminhtml\Listing\Helper\Form\Gallery;

use Magento\Framework\App\ObjectManager;
use Magento\Backend\Block\Media\Uploader;
use Magento\Framework\View\Element\AbstractBlock;

class Content extends \Magento\Backend\Block\Widget
{

    protected $_template = 'business/form/gallery.phtml';

    protected $_jsonEncoder;

    private $imageUploadConfigDataProvider;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        array $data = [],
        $imageUploadConfigDataProvider = null
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        parent::__construct($context, $data);
        try {
            /* Try for old magento version where ImageUploadConfigDataProvider does not exist */
            if (class_exists(\Magento\Backend\Block\DataProviders\ImageUploadConfig::class)) {
                $this->imageUploadConfigDataProvider = $imageUploadConfigDataProvider
                    ?: ObjectManager::getInstance()->get(\Magento\Backend\Block\DataProviders\ImageUploadConfig::class);
            } elseif (class_exists(\Magento\Backend\Block\DataProviders\UploadConfig::class)) {
                /* Workaround for Magento 2.2.8 */
                $this->imageUploadConfigDataProvider = ObjectManager::getInstance()->get(
                    \Magento\Backend\Block\DataProviders\UploadConfig::class
                );
            }
        } catch (\Exception $e) {
            return;
        }
    }

    protected function _prepareLayout()
    {
        $this->addChild(
            'uploader',
            \Magento\Backend\Block\Media\Uploader::class,
            ['image_upload_config_data' => $this->imageUploadConfigDataProvider]
        );

        $this->getUploader()->getConfig()->setUrl(
            $this->_urlBuilder->getUrl('business/listing_upload/gallery')
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

        $this->_eventManager->dispatch('business_listing_gallery_prepare_layout', ['block' => $this]);

        return parent::_prepareLayout();
    }

    public function getUploader()
    {
        return $this->getChildBlock('uploader');
    }

    public function getUploaderHtml()
    {
        return $this->getChildHtml('uploader');
    }

    public function getJsObjectName()
    {
        return $this->getHtmlId() . 'JsObject';
    }

    public function getAddImagesButton()
    {
        return $this->getButtonHtml(
            __('Add New Images'),
            $this->getJsObjectName() . '.showUploader()',
            'add',
            $this->getHtmlId() . '_add_images_button'
        );
    }

    public function getImagesJson()
    {
        $value = $this->getElement()->getImages();
        if (is_array($value) &&
            array_key_exists('images', $value) &&
            is_array($value['images']) &&
            count($value['images'])
        ) {
            $images = $this->sortImagesByPosition($value['images']);

            return $this->_jsonEncoder->encode($images);
        }
        return '[]';
    }

    private function sortImagesByPosition($images)
    {
        if (is_array($images)) {
            usort($images, function ($imageA, $imageB) {
                return ($imageA['position'] < $imageB['position']) ? -1 : 1;
            });
        }
        return $images;
    }
}