<?php

declare(strict_types=1);

namespace OnitsukaTiger\Catalog\Plugin\Helper;

use Magento\Catalog\Model\Product as ModelProduct;
use Magento\Catalog\Model\Product\Image\UrlBuilder;

/**
 * Class Helper Data
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data
{
    /**
     * @var UrlBuilder
     */
    private $imageUrlBuilder;

    /**
     * Data constructor.
     * @param UrlBuilder $urlBuilder
     */
    public function __construct(
        UrlBuilder $urlBuilder
    ) {
        $this->imageUrlBuilder = $urlBuilder;
    }

    public function afterGetProductMediaGallery(\Magento\Swatches\Helper\Data $subject, $result, ModelProduct $product) {
        if($product->getBaseMouseoverImage()){
            $result['base_mouseover_image'] = $this->imageUrlBuilder->getUrl($product->getBaseMouseoverImage(), 'category_page_grid_base_second');
        }

        return $result;
    }
}
