<?php
namespace OnitsukaTiger\CategoryModelImage\Model\CategoryModelImage\Gallery;

use Magento\Framework\Model\AbstractModel;

class Image extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\OnitsukaTiger\CategoryModelImage\Model\ResourceModel\CategoryModelImage\Gallery\Image::class);
    }
}
