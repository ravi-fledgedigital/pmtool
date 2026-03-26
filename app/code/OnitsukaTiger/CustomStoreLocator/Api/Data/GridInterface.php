<?php

namespace OnitsukaTiger\CustomStoreLocator\Api\Data;

interface GridInterface{
    const STORE_ID = 'id';
    const STORE_NAME = 'store_name';
    const STORE_ADD = 'store_address';
    const STORE_PHONE_NO = 'store_phone_no';
    const TIME_STARTED = 'time_started';
    const TIME_COMPLETED = 'time_completed';
    const STORE_NOTES = 'store_notes';
    const GOOGLE_MAP_LINK = 'google_map_link';
    const STORE_IMAGES = 'store_images';
    const POSITION = 'position';
    const STORE_STATUS = 'store_status';
    const META_TITLE = 'meta_title';
    const META_KEYWORD = 'meta_keyword';
    const META_DESCRIPTION = 'meta_description';

    public function getStoreId();

    public function setStoreId($store_id);

    public function getStoreName();

    public function setStoreName($storeName);

    public function getStoreAdd();

    public function setStoreAdd($storeAdd);

    public function getStorePhoneNo();

    public function setStorePhoneNo($storePhoneNo);

    public function getTimeStarted();

    public function setTimeStarted($time_started);

    public function getTimeCompleted();

    public function setTimeCompleted($time_completed);

    public function getStoreNotes();

    public function setStoreNotes($storeNotes);

    public function getGoogleMapLink();

    public function setGoogleMapLink($googleMapLink);

    public function getStoreImage();

    public function setStoreImage($storeImage);

    public function getPosition();

    public function setPosition($position);

    public function getStoreStatus();

    public function setStoreStatus($storeStatus);

    public function getMetaTitle();

    public function setMetaTitle($metaTitle);

    public function getMetaKeyword();

    public function setMetaKeyword($metaKeyword);

    public function getMetaDescription();

    public function setMetaDescription($metaDescription);

    
}