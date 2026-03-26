<?php

namespace OnitsukaTigerCpss\Crm\Observer;

use Cpss\Crm\Plugin\Customer\CpssCreateAccount;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;

class CreateCustomer implements \Magento\Framework\Event\ObserverInterface
{
    public const AGREED = 1;
    /**
     * @var CustomerRepositoryInterface
     */
    private $_customerRepositoryInterface;

    /**
     * @var CpssCreateAccount
     */
    private $cpssCreateAccount;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * CreateCustomer constructor.
     *
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param CpssCreateAccount $cpssCreateAccount
     * @param Session $customerSession
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepositoryInterface,
        CpssCreateAccount $cpssCreateAccount,
        Session $customerSession,
    ) {
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->cpssCreateAccount = $cpssCreateAccount;
        $this->customerSession = $customerSession;
    }

    /**
     * Add agreement create customer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $this->_customerRepositoryInterface->getById($observer->getEvent()->getCustomer()->getId());
        $customer->setCustomAttribute('is_agreed', self::AGREED);
        $this->_customerRepositoryInterface->save($customer);
        //if (!$customer->getConfirmation()) {
        $this->cpssCreateAccount->createAccount();
        //}

        return $this;
    }
}
