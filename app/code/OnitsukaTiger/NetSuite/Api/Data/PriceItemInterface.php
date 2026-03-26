<?php

namespace OnitsukaTiger\NetSuite\Api\Data;

interface PriceItemInterface
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
     * @param $code
     * @return mixed
     */
    public function setWebsiteCode($code);

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