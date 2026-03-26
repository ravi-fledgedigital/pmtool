<?php

namespace OnitsukaTigerCpss\Crm\Controller\Register;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class AddMember extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var \Cpss\Crm\Model\CpssApiRequest
     */
    protected $cpssApiRequest;

    /**
     * @var \OnitsukaTigerCpss\Crm\Helper\HelperData
     */
    protected $helper;

    /**
     * @var \Cpss\Crm\Helper\Customer
     */
    protected $crmHelper;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * History construct class
     *
     * @param Context $context
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        \Cpss\Crm\Model\CpssApiRequest $cpssApiRequest,
        \OnitsukaTigerCpss\Crm\Helper\HelperData $helper,
        \Cpss\Crm\Helper\Customer $crmHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->cpssApiRequest = $cpssApiRequest;
        $this->helper = $helper;
        $this->crmHelper = $crmHelper;
        $this->customerRepository = $customerRepository;
    }

    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            return $this->_redirect('customer/account');
        }

        if (!$this->customerSession->getPointServiceEnabled()) {
            return $this->_redirect('customer/account');
        }

        if ($this->customerSession->isLoggedIn() && !$this->helper->checkAgreement()) {
            $customer = $this->customerSession->getCustomer();
            // Register to CPSS
            $this->cpssApiRequest->addMember($this->crmHelper->getCpssMembeIdPrefix() . $customer->getId());

            $customer = $this->customerRepository->get($customer->getEmail(), $customer->getWebsiteId());
            $customer->setCustomAttribute('is_agreed', 1);
            $gender = $customer->getGender();
            if (!$gender) {
                $customer->setGender(5308);
            }
            $this->customerRepository->save($customer);
        }

        $redirectUrl = $this->_redirect->getRedirectUrl();

        return $this->_redirect($redirectUrl);
    }
}
