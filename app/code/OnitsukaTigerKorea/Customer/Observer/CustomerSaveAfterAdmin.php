<?php

namespace OnitsukaTigerKorea\Customer\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class CustomerSaveAfterAdmin implements ObserverInterface
{
    protected $request;
    protected $customerRepository;

    public function __construct(
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->request = $request;
        $this->customerRepository = $customerRepository;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */

        $customer = $observer->getEvent()->getCustomer();
        $customData = $this->request->getPostValue();

        if (isset($customData['customer']['subscribe_kakao'])) {
            $customValue = $customData['customer']['subscribe_kakao'];

            $customer->setCustomAttribute('subscribe_kakao', $customValue);
            $this->customerRepository->save($customer);
        }
    }
}
