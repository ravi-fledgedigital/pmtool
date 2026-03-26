<?php

namespace OnitsukaTiger\Customer\Plugin\Model;


use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Customer\Model\ForgotPasswordToken\GetCustomerByToken;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ObjectManager;

class AccountManagement
{

    /**
     * @var \Magento\Customer\Model\EmailNotificationInterface
     */
    private $emailNotification;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var GetCustomerByToken
     */
    private $getByToken;

    /**
     * FormPost constructor.
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        GetCustomerByToken $getByToken = null,
        Session $customerSession
    ) {
        $objectManager = ObjectManager::getInstance();
        $this->session = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->getByToken = $getByToken
            ?: $objectManager->get(GetCustomerByToken::class);
    }
    /**
     * @inheritdoc
     */
    public function aroundResetPassword(\Magento\Customer\Model\AccountManagement $subject, callable $proceed, $email, $resetToken, $newPassword)
    {
        if (!$email) {
            $customer = $this->getByToken->execute($resetToken);
            $email = $customer->getEmail();
        } else {
            $customer = $this->customerRepository->get($email);
        }
        $result = $proceed($email, $resetToken, $newPassword);
        if($result){
            $this->getEmailNotification()->credentialsChanged(
                $customer,
                $customer->getEmail(),
                true
            );
        }
    }

    /**
     * Get email notification
     *
     * @return EmailNotificationInterface
     * @deprecated 100.1.0
     */
    private function getEmailNotification()
    {
        if (!($this->emailNotification instanceof EmailNotificationInterface)) {
            return ObjectManager::getInstance()->get(
                EmailNotificationInterface::class
            );
        } else {
            return $this->emailNotification;
        }
    }

    /**
     * Get customer data object
     *
     * @param int $customerId
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    private function getCustomerDataObject($customerId)
    {
        return $this->customerRepository->getById($customerId);
    }
}
