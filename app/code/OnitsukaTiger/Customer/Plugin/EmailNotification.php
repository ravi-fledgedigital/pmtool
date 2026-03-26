<?php

namespace OnitsukaTiger\Customer\Plugin;

/**
 * Class EmailNotification
 * @package OnitsukaTiger\Customer\Plugin
 */
class EmailNotification {
    public function aroundNewAccount(
        \Magento\Customer\Model\EmailNotification $subject,
        \Closure $proceed,
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        $type,
        $backUrl = '',
        $storeId = 0,
        $sendemailStoreId = null
    ) {

        if($type == 'confirmed' || $type == 'registered'){
            return false;
        }
        $result = $proceed($customer ,$type ,$backUrl ,$storeId ,$sendemailStoreId);
        return $result;
    }
}
