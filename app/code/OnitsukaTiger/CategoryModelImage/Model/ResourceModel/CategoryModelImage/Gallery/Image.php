<?php
namespace OnitsukaTiger\CategoryModelImage\Model\ResourceModel\CategoryModelImage\Gallery;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Image extends AbstractDb
{
    protected function _construct() {
        $this->_init('catalog_categories_entity_media_gallery', 'entity_id');
    }
}
