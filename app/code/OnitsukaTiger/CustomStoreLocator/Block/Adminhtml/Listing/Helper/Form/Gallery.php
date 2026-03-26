<?php
namespace OnitsukaTiger\CustomStoreLocator\Block\Adminhtml\Listing\Helper\Form;

use Magento\Framework\Registry;

class Gallery extends \Magento\Framework\View\Element\AbstractBlock
{

    protected $fieldNameSuffix = 'business_list';

    protected $htmlId = 'media_gallery';

    protected $name = 'media_gallery';

    protected $image = 'image';

    protected $formName = 'business_form';

    protected $form;

    protected $registry;

    protected $gridFactory;

    protected $storeManager;

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        Registry $registry,
        \Magento\Framework\Data\Form $form,
        \OnitsukaTiger\CustomStoreLocator\Model\GridFactory $gridFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        $data = []
    ) {
        $this->registry = $registry;
        $this->form = $form;
        $this->gridFactory = $gridFactory;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    public function getElementHtml()
    {
        return $this->getContentHtml();
    }

    public function getImages()
    {
        $result = [];
        $gallery = [];
        $id = $this->registry->registry('id');
        $businessList = $this->gridFactory->create()->getCollection()->addFieldToFilter('id', $id)->getFirstItem();
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        if ($businessList->getMediaGallery()) {
            $gallery = explode(";", $businessList->getMediaGallery());
        }
        if (count($gallery)) {
            $result['images'] = [];
            $position = 1;
            foreach ($gallery as $image) {
                $label = str_replace("business/business/mediagallery/","",$image);
                $result['images'][] = [
                    'value_id' => $image,
                    'file' => $image,
                    'label' => $label,
                    'position' => $position,
                    'url' => $mediaUrl.$image,
                ];
                $position++;
            }
        }

        return $result;
    }

    public function getContentHtml()
    {
        $content = $this->getChildBlock('content');
        if (!$content) {
            $content = $this->getLayout()->createBlock(\OnitsukaTiger\CustomStoreLocator\Block\Adminhtml\Listing\Helper\Form\Gallery\Content::class,
                '',
                [
                    'config' => [
                        'parentComponent' => 'business_form.business_form.block_gallery.block_gallery'
                    ]
                ]
            );
        }

        $content
            ->setId($this->getHtmlId() . '_content')
            ->setElement($this)
            ->setFormName($this->formName);
        $galleryJs = $content->getJsObjectName();
        $content->getUploader()->getConfig()->setMegiaGallery($galleryJs);
        return $content->toHtml();
    }

    protected function getHtmlId()
    {
        return $this->htmlId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFieldNameSuffix()
    {
        return $this->fieldNameSuffix;
    }

    public function getDataScopeHtmlId()
    {
        return $this->image;
    }

    public function toHtml()
    {
        return $this->getElementHtml();
    }
}