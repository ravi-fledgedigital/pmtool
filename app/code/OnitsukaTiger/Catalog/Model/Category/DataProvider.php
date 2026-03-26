<?php
namespace OnitsukaTiger\Catalog\Model\Category;

use Magento\Catalog\Model\Category\Attribute\Backend\Image as ImageBackendModel;
use Magento\Catalog\Model\Category\FileInfo;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem;

/**
 * Class DataProvider
 * @package OnitsukaTiger\Catalog\Model\Category
 */
class DataProvider extends \Magento\Catalog\Model\Category\DataProvider
{

    /**
     * @var Filesystem
     */
    private $fileInfo;
    /**
     * Get data
     *
     * @return array
     * @since 101.0.0
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $category = $this->getCurrentCategory();
        if ($category) {
            $categoryData = $category->getData();
            $categoryData = $this->addUseConfigSettings($categoryData);
            $categoryData = $this->filterFields($categoryData);
            $categoryData = $this->convertValues($category, $categoryData);

            $this->loadedData[$category->getId()] = $categoryData;
        }

        return $this->loadedData;
    }
    /**
     * Converts category image data to acceptable for rendering format
     *
     * @param \Magento\Catalog\Model\Category $category
     * @param array $categoryData
     * @return array
     */
    private function convertValues($category, $categoryData): array
    {
        foreach ($category->getAttributes() as $attributeCode => $attribute) {
            if ($attributeCode === 'custom_layout_update_file') {
                if (!empty($categoryData['custom_layout_update'])) {
                    $categoryData['custom_layout_update_file']
                        = \Magento\Catalog\Model\Category\Attribute\Backend\LayoutUpdate::VALUE_USE_UPDATE_XML;
                }
            }
            if (!isset($categoryData[$attributeCode])) {
                continue;
            }

            if ($attribute->getBackend() instanceof ImageBackendModel) {
                unset($categoryData[$attributeCode]);
                if($category->getData($attributeCode)){
                    $fileName = str_replace('/tmp/','/',$category->getData($attributeCode));
                    $fileInfo = $this->getFileInfo();

                    if ($fileInfo->isExist($fileName)) {
                        $stat = $fileInfo->getStat($fileName);
                        $mime = $fileInfo->getMimeType($fileName);

                        // phpcs:ignore Magento2.Functions.DiscouragedFunction
                        $categoryData[$attributeCode][0]['name'] = basename($fileName);

                        if ($fileInfo->isBeginsWithMediaDirectoryPath($fileName)) {
                            $categoryData[$attributeCode][0]['url'] = $fileName;
                        } else {
                            $categoryData[$attributeCode][0]['url'] = $category->getImageUrl($attributeCode);
                        }

                        $categoryData[$attributeCode][0]['size'] = isset($stat) ? $stat['size'] : 0;
                        $categoryData[$attributeCode][0]['type'] = $mime;
                    }
                }
            }
        }

        return $categoryData;
    }
    /**
     * Get FileInfo instance
     *
     * @return FileInfo
     *
     * @deprecated 102.0.0
     */
    private function getFileInfo(): FileInfo
    {
        if ($this->fileInfo === null) {
            $this->fileInfo = ObjectManager::getInstance()->get(FileInfo::class);
        }
        return $this->fileInfo;
    }
    /**
     * @return array
     */
    protected function getFieldsMap()
    {
        $fields = parent::getFieldsMap();
        $fields['content'][] = 'banner_image';

        return $fields;
    }
}
