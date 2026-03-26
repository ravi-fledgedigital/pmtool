<?php

namespace OnitsukaTigerCpss\Crm\Plugin\Customer;

use Cpss\Crm\Plugin\Customer\CpssCreateAccount;
use Magento\Customer\Model\AccountManagement;

/**
 * Customer After Confirm
 */
class CustomerConfirm
{
    public const AGREED = 1;

    /**
     * @var CpssCreateAccount
     */
    private $cpssCreateAccount;

    /**
     * Customer Confirm constructor.
     * @param CpssCreateAccount $cpssCreateAccount
     */
    public function __construct(
        CpssCreateAccount $cpssCreateAccount
    ) {
        $this->cpssCreateAccount = $cpssCreateAccount;
    }

    /**
     * After Customer Activate
     *
     * @param AccountManagement $subject
     * @param $customer
     * @return mixed
     */
    public function afterActivate(AccountManagement $subject, $customer)
    {
        $is_agreed = $customer->getCustomAttribute('is_agreed')->getValue();
        if ($is_agreed == self::AGREED) {
            $this->cpssCreateAccount->createAccount();
        }
        return $customer;
    }
}
