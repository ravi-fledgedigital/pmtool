<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepBase\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use \Vaimo\AepBase\Service\CartService;

class ProductAddAfterObserver implements ObserverInterface
{
	/**
     * @var \Vaimo\AepBase\Service\CartService
     */
    protected $cartService;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote
     */
    protected $quote;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Observer constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Vaimo\AepBase\Service\CartService $cartService
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
     	\Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        CartService $cartService,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {

        $this->quote = $checkoutSession->getQuote();
        $this->messageManager = $messageManager;
        $this->cartService = $cartService;
        $this->quoteRepository = $quoteRepository;
        $this->customerRepository = $customerRepository;
    }

    public function execute(Observer $observer): void
    {
        if($this->quote->getId()) {
        	$quoteData = $this->quoteRepository->get($this->quote->getId());
        	if ($quoteData->getCustomerId()) {
        		$customerId = $quoteData->getCustomerId();
		        try {
		        	$customer = $this->customerRepository->getById($customerId);
		        	// update the customer
		            $this->cartService->updateCustomer($customer, $quoteData->getUpdatedAt());
		        } catch (\Exception $e) {
		            $this->messageManager->addErrorMessage(__($e->getMessage()));
		        }
        	}
	    }
    }
}
