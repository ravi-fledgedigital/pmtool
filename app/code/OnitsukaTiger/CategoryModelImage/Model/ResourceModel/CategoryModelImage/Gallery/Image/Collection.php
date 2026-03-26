<?php
namespace OnitsukaTiger\CategoryModelImage\Model\ResourceModel\CategoryModelImage\Gallery\Image;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Data\Collection as DataCollection;

class Collection extends AbstractCollection
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \OnitsukaTiger\CategoryModelImage\Model\CategoryModelImage\Gallery\Image::class,
            \OnitsukaTiger\CategoryModelImage\Model\ResourceModel\CategoryModelImage\Gallery\Image::class
        );
    }

    /**
     * @param int $categoryId
     * @param bool $frontendArea
     * @param int $length
     * @return Collection
     */
    public function getByCategoryId(int $categoryId, $frontendArea = false,$length = 0)
    {
        if($frontendArea) {
            return $this->_reset()
                ->addFieldToFilter('category_id', $categoryId)
                ->addFieldToFilter('store', '0')
                ->addFieldToFilter('disabled', '0')
                ->addFieldToFilter('position', array('gt' => 0))
                ->addFieldToFilter('position', array('lt' => $length))
                ->setOrder('entity_id', DataCollection::SORT_ORDER_ASC)
                ->load();
        }else{
            return $this->_reset()
                ->addFieldToFilter('category_id', $categoryId)
                ->addFieldToFilter('store', '0')
                ->setOrder('entity_id', DataCollection::SORT_ORDER_ASC)
                ->load();
        }
    }

    /**
     * @param int $categoryId
     * @param int $store
     * @param bool $frontendArea
     * @param int $length
     * @return Collection
     */
    public function getByCategoryIdStore(int $categoryId, int $store, $frontendArea = false, $length = 0)
    {
        if($frontendArea) {
            return $this->_reset()
                ->addFieldToFilter('category_id', $categoryId)
                ->addFieldToFilter('store', $store)
                ->addFieldToFilter('disabled', '0')
                ->addFieldToFilter('position', array('gt' => 0))
                ->addFieldToFilter('position', array('lt' => $length))
                ->setOrder('entity_id', DataCollection::SORT_ORDER_ASC)
                ->load();
        }else{
            return $this->_reset()
                ->addFieldToFilter('category_id', $categoryId)
                ->addFieldToFilter('store', $store)
                ->setOrder('entity_id', DataCollection::SORT_ORDER_ASC)
                ->load();
        }
    }
}
