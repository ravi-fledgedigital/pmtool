<?php

namespace OnitsukaTiger\Catalog\Magento\Catalog\Model\Product;

class Url extends \Magento\Catalog\Model\Product\Url
{
    /**
     * Format Key for URL
     *
     * @param string $str
     * @return string
     */
    public function formatUrlKey($str)
    {
        if (empty($str)) {
            return $str;
        }
        return trim(strtolower($str));
    }
}