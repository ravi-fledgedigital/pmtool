<?php

namespace Cpss\Crm\Plugin\Customer;

class CheckoutLogin extends CpssCreateAccount
{
    public function afterExecute(\Magento\Customer\Controller\Ajax\Login $subject, $proceed)
    {
        if ($this->customerSession->isLoggedIn()) {
            $this->createAccount(); //disable current version
        }
        return $proceed;
    }
}
