<?php
namespace Vaimo\OTScene7Integration\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;

class ResizeImage implements ArgumentInterface
{
    public const RESIZE_IMAGE_TYPE_PRESCHOOL = 'PRESCHOOL';
    public const RESIZE_IMAGE_TYPE_TODDLER = 'TODDLER';
    public const RESIZE_IMAGE_TYPE_DEFAULT = 'NO';
    public const RESIZE_IMAGE_NAME_PRESCHOOL = 'category-page-grid-base-preschool';
    public const RESIZE_IMAGE_NAME_SECOND_PRESCHOOL = 'category_page_grid_base_second_preschool';
    public const RESIZE_IMAGE_NAME_TODDLER = 'category-page-grid-base-toddlers';
    public const RESIZE_IMAGE_NAME_SECOND_TODDLER = 'category_page_grid_base_second_toddlers';
    public const RESIZE_IMAGE_NAME_DEFAULT = 'category_page_grid_base';
    public const RESIZE_IMAGE_NAME_SECOND_DEFAULT = 'category_page_grid_base_second';
    /**
     * Get resize image by condition
     *
     * @param mixed $_product
     * @return string[]
     */
    public function getResizeImage($_product): array
    {
        $kidTypeProduct = $_product->getKidTypeProduct()
           ? $_product->getAttributeText('kid_type_product') : self::RESIZE_IMAGE_TYPE_DEFAULT;
        if ($kidTypeProduct == self::RESIZE_IMAGE_TYPE_PRESCHOOL) {
            $imageId = self::RESIZE_IMAGE_NAME_PRESCHOOL;
            $imageIdSecond = self::RESIZE_IMAGE_NAME_SECOND_PRESCHOOL;
        } elseif ($kidTypeProduct == self::RESIZE_IMAGE_TYPE_TODDLER) {
            $imageId = self::RESIZE_IMAGE_NAME_TODDLER;
            $imageIdSecond = self::RESIZE_IMAGE_NAME_SECOND_TODDLER;
        } else {
            $imageId = self::RESIZE_IMAGE_NAME_DEFAULT;
            $imageIdSecond = self::RESIZE_IMAGE_NAME_SECOND_DEFAULT;
        }

        return [
            "imageId" => $imageId,
            "imageIdSecond" => $imageIdSecond
        ];
    }
}
