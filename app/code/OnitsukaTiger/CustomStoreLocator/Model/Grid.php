<?php

namespace OnitsukaTiger\CustomStoreLocator\Model;
use Magento\Framework\Model\AbstractModel;
use OnitsukaTiger\CustomStoreLocator\Api\Data\GridInterface;

class Grid extends AbstractModel implements GridInterface
{
    protected function _construct()
    {
        $this->_init(\OnitsukaTiger\CustomStoreLocator\Model\ResourceModel\Grid::class);
    }

    public function getStoreId()
    {
        $this->getData(self::STORE_ID);
    }

    public function setStoreId($store_id)
    {
        $this->setData(self::STORE_ID,$store_id);
    }

    public function getStoreName()
    {
        $this->getData(self::STORE_NAME);
    }

    public function setStoreName($storeName)
    {
        $this->setData(self::STORE_NAME, $storeName);
    }

    public function getStoreAdd()
    {
        $this->getData(self::STORE_ADD);
    }

    public function setStoreAdd($storeAdd)
    {
        $this->setData(self::STORE_ADD,$storeAdd);
    }

    public function getStorePhoneNo()
    {
        $this->getData(self::STORE_PHONE_NO);
    }

    public function setStorePhoneNo($storePhoneNo)
    {
        $this->setData(self::STORE_PHONE_NO, $storePhoneNo);
    }

    public function getTimeStarted()
    {
        $this->getData(self::TIME_STARTED);
    }

    public function setTimeStarted($time_started)
    {
        $this->setData(self::TIME_STARTED, $time_started);
    }

    public function getTimeCompleted()
    {
        $this->getData(self::TIME_COMPLETED);
    }

    public function setTimeCompleted($time_completed)
    {
        $this->setData(self::TIME_COMPLETED, $time_completed);
    }


    public function getStoreNotes()
    {
        $this->getData(self::STORE_NOTES);
    }

    public function setStoreNotes($storeNotes)
    {
        $this->setData(self::STORE_NOTES, $storeNotes);
    }

    public function getGoogleMapLink()
    {
        $this->getData(self::GOOGLE_MAP_LINK);
    }

    public function setGoogleMapLink($googleMapLink)
    {
        $this->setData(self::GOOGLE_MAP_LINK,$googleMapLink);
    }

    public function getStoreImage()
    {
        $this->getData(self::STORE_IMAGES);
    }

    public function setStoreImage($storeImage)
    {
        $this->setData(self::STORE_IMAGES,$storeImage);
    }

    public function getPosition()
    {
        $this->getData(self::POSITION);
    }

    public function setPosition($position)
    {
        $this->setData(self::POSITION,$position);
    }

    public function getStoreStatus()
    {
        $this->getData(self::STORE_STATUS);
    }

    public function setStoreStatus($storeStatus)
    {
        $this->setData(self::STORE_STATUS,$storeStatus);
    }

    public function getMetaTitle()
    {
        $this->getData(self::META_TITLE);
    }

    public function setMetaTitle($metaTitle)
    {
        $this->setData(self::META_TITLE,$metaTitle);
    }

    public function getMetaKeyword()
    {
        $this->getData(self::META_KEYWORD);
    }

    public function setMetaKeyword($metaKeyword)
    {
        $this->setData(self::META_KEYWORD,$metaKeyword);
    }

    public function getMetaDescription()
    {
        $this->getData(self::META_DESCRIPTION);
    }

    public function setMetaDescription($metaDescription)
    {
        $this->setData(self::META_DESCRIPTION, $metaDescription);
    }

    

}