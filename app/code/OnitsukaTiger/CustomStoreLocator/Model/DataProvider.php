<?php
namespace OnitsukaTiger\CustomStoreLocator\Model;
 
use OnitsukaTiger\CustomStoreLocator\Model\ResourceModel\Grid\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
 
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    protected $loadedData;
    protected $storeManager;
 
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }
 
    public function getData()
    {
        if ($this->loadedData === null) {
            foreach ($this->collection->getItems() as $item) {
                $itemData = $item->getData();
                
                if (!empty($itemData['time_started'])) {
                    $itemData['time_started'] = date('H:i', strtotime($itemData['time_started']));
                }

                if (!empty($itemData['time_completed'])) {
                    $itemData['time_completed'] = date('H:i', strtotime($itemData['time_completed']));
                }

                $images = [];
                $galleryJson = $itemData['gallery'] ?? null;

                if ($galleryJson) {
                    $imageNames = json_decode($galleryJson, true);

                    if (is_array($imageNames)) {
                        foreach ($imageNames as $image) {
                            $imagePath = BP . '/pub/media/gallery/image/' . $image;
                            $size = file_exists($imagePath) ? filesize($imagePath) : 0;

                            $images[] = [
                                'name' => $image,
                                'url' => $this->getMediaUrl() . $image,
                                'type' => 'image',
                                'size' => $size,
                            ];
                        }
                    }
                }

                $itemData['gallery'] = $images;

                $mobileimages = [];
                $mobilegalleryJson = $itemData['mobile_gallery'] ?? null;

                if ($mobilegalleryJson) {
                    $mobileimageNames = json_decode($mobilegalleryJson, true);

                    if (is_array($mobileimageNames)) {
                        foreach ($mobileimageNames as $mobileimage) {
                            $imagePath = BP . '/pub/media/mobile_gallery/image/' . $mobileimage;
                            $size = file_exists($imagePath) ? filesize($imagePath) : 0;

                            $mobileimages[] = [
                                'name' => $mobileimage,
                                'url' => $this->getMobileMediaUrl() . $mobileimage,
                                'type' => 'image',
                                'size' => $size,
                            ];
                        }
                    }
                }

                $itemData['mobile_gallery'] = $mobileimages;

                $this->loadedData[$item->getId()] = $itemData;

                break; // Remove this if you're editing more than 1 item
            }
        }

        return $this->loadedData;
    }

    protected function getMediaUrl()
    {
        return $this->storeManager
            ->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'gallery/image/';
    }

    protected function getMobileMediaUrl()
    {
        return $this->storeManager
            ->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'mobile_gallery/image/';
    }
}
