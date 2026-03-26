<?php

namespace Cpss\Crm\Plugin\Customer;

class Create extends CpssCreateAccount
{
    public function afterExecute(\Magento\Customer\Controller\Account\CreatePost $subject, $proceed)
    {
        /*if ($this->customerSession->isLoggedIn()) {*/
            $this->createAccount(); //disable current version
        /*}*/
        return $proceed;
    }
}
