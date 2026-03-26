<?php

namespace OnitsukaTigerCpss\Crm\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerLogin implements ObserverInterface
{
    /**
     * @var \Magento\Customer\Model\Session
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

    protected $crmHelper;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    protected $storeManager;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Cpss\Crm\Model\CpssApiRequest $cpssApiRequest,
        \OnitsukaTigerCpss\Crm\Helper\HelperData $helper,
        \Cpss\Crm\Helper\Customer $crmHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->customerSession = $customerSession;
        $this->cpssApiRequest = $cpssApiRequest;
        $this->helper = $helper;
        $this->crmHelper = $crmHelper;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->customerSession->isLoggedIn() && !$this->helper->checkAgreement()) {
            $customer = $observer->getEvent()->getCustomer();
            $websiteId = $this->storeManager->getWebsite()->getId();
            // Register to CPSS
            $this->cpssApiRequest->addMember($this->crmHelper->getCpssMembeIdPrefix() . $customer->getId());

            $customer = $this->customerRepository->get($customer->getEmail(), $customer->getWebsiteId());
            // $customer->setCustomAttribute('is_agreed', 1);
            //$this->customerRepository->save($customer);

            if($websiteId == 4){
                $customer->setCustomAttribute('is_agreed', 0);
            }else{
                $customer->setCustomAttribute('is_agreed', 1);
            }
            return $this->customerRepository->save($customer);
        }
    }
}
