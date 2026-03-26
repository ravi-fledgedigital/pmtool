<?php

namespace OnitsukaTiger\DiscountLimit\Model;

/**
 * @package    OnitsukaTiger_DiscountLimit
 */
interface ConfigInterface
{
    /**
     * Get configuration boolean value
     *
     * @param string $xmlPath
     * @param int $storeId
     * @return bool
     */
    public function getConfigFlag($xmlPath, $storeId = null);

    /**
     * Get configuration value
     *
     * @param string $xmlPath
     * @param int $storeId
     * @return string
     */
    public function getConfigValue($xmlPath, $storeId = null);
}
