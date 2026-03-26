<?php

namespace OnitsukaTiger\InventorySourceAlgorithm\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Data
 * @package OnitsukaTiger\InventorySourceAlgorithm\Helper
 */
class Data extends AbstractHelper
{
    const XML_PATH_SOURCE_SELECTION = 'source_algorithm/';

    /**
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * @param $code
     * @param null $storeId
     * @return mixed
     */
    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SOURCE_SELECTION .'source_algorithm_list/'. $code, $storeId);
    }

}
