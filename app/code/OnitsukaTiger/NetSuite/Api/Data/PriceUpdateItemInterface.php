<?php
namespace OnitsukaTiger\NetSuite\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface PriceUpdateItemInterface extends ExtensibleDataInterface
{
    /**
     * @return mixed
     */
    public function getSku();

    /**
     * @param $sku
     * @return mixed
     */
    public function setSku($sku);

    /**
     * @return mixed
     */
    public function getWebsiteCode();

    /**
     * @param $websiteCode
     * @return mixed
     */
    public function setWebsiteCode($websiteCode);

    /**
     * @return mixed
     */
    public function getPrice();

    /**
     * @param $price
     * @return mixed
     */
    public function setPrice($price);
}