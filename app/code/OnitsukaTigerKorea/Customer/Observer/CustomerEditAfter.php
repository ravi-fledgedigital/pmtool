<?php

declare(strict_types=1);

namespace OnitsukaTigerKorea\Customer\Observer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;

class CustomerEditAfter implements ObserverInterface
{
    private CustomerRepositoryInterface $customerRepository;
    private RequestInterface $request;
    private LoggerInterface $logger;
    private Session $customerSession;

    public function __construct(
        LoggerInterface $logger,
        CustomerRepositoryInterface $customerRepository,
        RequestInterface $request,
        Session $customerSession
    ) {
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->request = $request;
        $this->customerSession = $customerSession;
    }

    public function execute(Observer $observer): void
    {
        try {
            $offlineStoreId = $this->request->getParam('offline_store_id');
            $customerId = $this->customerSession->getCustomerId();
            if (!$customerId || $offlineStoreId === null) {
                return;
            }
            $customer = $this->customerRepository->getById($customerId);
            $customer->setCustomAttribute('offline_store_id', $offlineStoreId);
            $this->customerRepository->save($customer);

        } catch (\Exception $e) {
            $this->logger->critical('Error saving offline_store_id', ['exception' => $e]);
        }
    }
}
