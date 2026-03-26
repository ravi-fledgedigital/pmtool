<?php
namespace OnitsukaTiger\CategoryModelImage\Plugin\Catalog\Block;

/**
 * Class Toolbar
 * @package OnitsukaTiger\CategorySort\Plugin\Catalog\Block
 */
class Toolbar
{

    protected $_imageHelper;


    /**
     * Toolbar constructor.
     * @param \OnitsukaTiger\CategoryModelImage\Helper\Data $helper
     */
    public function __construct(
        \OnitsukaTiger\CategoryModelImage\Helper\Data $helper
    ) {
        $this->_imageHelper = $helper;
    }

    /**
     * @param \Magento\Catalog\Block\Product\ProductList\Toolbar $subject
     * @param $result
     * @return mixed
     */
    public function afterGetLimit(\Magento\Catalog\Block\Product\ProductList\Toolbar $subject, $result) {
        $totalImage = $this->_imageHelper->getCategoryModelImageImage($result);
        if($totalImage){
            $resultPager = $result - count($totalImage);
            if($resultPager) {
                return $resultPager;
            }
        }
        return $result;
    }
}
