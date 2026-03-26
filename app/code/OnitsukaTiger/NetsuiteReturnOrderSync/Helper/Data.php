<?php

namespace OnitsukaTiger\NetsuiteReturnOrderSync\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_RMA_NETSUITE = 'rma_netsuite/';

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_RMA_NETSUITE .'rma_sync/'. $code, $storeId);
    }

    public function getRmaAlgorithmConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_RMA_NETSUITE .'rma_algorithm/'. $code, $storeId);
    }

}
