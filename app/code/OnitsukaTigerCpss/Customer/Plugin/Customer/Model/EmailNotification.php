<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTigerCpss\Customer\Plugin\Customer\Model;

use Magento\Customer\Model\Session;
use OnitsukaTigerCpss\Crm\Helper\Data;

class EmailNotification
{

    /**
     * @var Session
     */
    private $session;
    public function __construct(
        Session $customerSession
    )
    {
        $this->session = $customerSession;
    }

    public function beforeNewAccount(
        \Magento\Customer\Model\EmailNotification $subject,
        $customer,
        $type = 'registered',
        $backUrl = '',
        $storeId = 0,
        $sendemailStoreId = null,
    ) {
        if ( $this->session->getIsRedirectAppLogin() ){
            $backUrl = $backUrl.'&'.Data::PARAMS_REGISTERED_FROM_APP.'=true';
        }

        return [$customer, $type, $backUrl, $storeId, $sendemailStoreId];
    }
}