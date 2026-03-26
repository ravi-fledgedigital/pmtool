<?php
namespace OnitsukaTiger\NetSuite\Model;

use OnitsukaTiger\NetSuite\Api\Data\PriceUpdateItemInterface;
use Magento\Framework\DataObject;

class PriceUpdateItem extends DataObject implements PriceUpdateItemInterface
{
    /**
     * @return array|mixed|null
     */
    public function getSku()
    {
        return $this->getData('sku');
    }

    /**
     * Set Items
     *
     * @param $items
     * @return void
     */
    public function setSku($sku)
    {
        $this->setData('sku', $sku);
    }

    /**
     * @return array|mixed|null
     */
    public function getWebsiteCode()
    {
        return $this->getData('websitecode');
    }

    /**
     * Set Website Code
     *
     * @param $websiteCode
     * @return void
     */
    public function setWebsiteCode($websiteCode)
    {
        $this->setData('websitecode', $websiteCode);
    }

    /**
     * @return array|mixed|null
     */
    public function getPrice()
    {
        return $this->getData('price');
    }

    /**
     * Set Price
     *
     * @param $price
     * @return void
     */
    public function setPrice($price)
    {
        $this->setData('price', $price);
    }
}