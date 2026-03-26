<?php

namespace OnitsukaTiger\EmailShipmentWithInvoice\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{

    const XML_PATH = 'sales_email/shipment/shipment_with_invoice';

    public function isEnableSendWithInvoice($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

}
