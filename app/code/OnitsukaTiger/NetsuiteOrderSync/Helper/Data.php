<?php

namespace OnitsukaTiger\NetsuiteOrderSync\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{

    const XML_PATH_SECTION = 'firebear_importexport/';
    const XML_PATH_NETSUITE_SYNC = 'netsuite/';

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    public function getNetsuiteInternalIdConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SECTION .'netsuite_internal_id/'. $code, $storeId);
    }

    public function enableOrderSyncWithMultiShipment($storeId = null) {
        return $this->getConfigValue(self::XML_PATH_NETSUITE_SYNC .'order_sync_multishipment/enabled', $storeId);
    }

    public function ignoreSourceStoreSyncToNetSuite($storeId = null) {
        return $this->getConfigValue(self::XML_PATH_NETSUITE_SYNC .'order_sync_multishipment/store', $storeId);
    }

}
