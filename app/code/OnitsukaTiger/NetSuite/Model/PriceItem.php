<?php

namespace OnitsukaTiger\NetSuite\Model;

use OnitsukaTiger\NetSuite\Api\Data\PriceItemInterface;
use Magento\Framework\DataObject;

class PriceItem extends DataObject implements PriceItemInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSku()
    {
        return $this->getData('sku');
    }
    /**
     * {@inheritdoc}
     */
    public function setSku($sku)
    {
        return $this->setData('sku', $sku);
    }
    /**
     * {@inheritdoc}
     */
    public function getWebsiteCode()
    {
        return $this->getData('websiteCode');
    }
    /**
     * {@inheritdoc}
     */
    public function setWebsiteCode($code)
    {
        return $this->setData('websiteCode', $code);
    }
    /**
     * {@inheritdoc}
     */
    public function getPrice()
    {
        return $this->getData('price');
    }
    /**
     * {@inheritdoc}
     */
    public function setPrice($price)
    {
        return $this->setData('price', $price);
    }
}