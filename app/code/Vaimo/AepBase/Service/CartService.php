<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepBase\Service;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Wishlist\Model\Wishlist as CustomerWishlist;
use Vaimo\AepBase\Api\ConfigInterface;
use Vaimo\AepBase\Model\ResourceModel\Wishlist as ResourceModel;

class CartService
{
    public const MAX_CHARACTER_LENGTH = 255; // limited to varchar(255) column length

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var \Vaimo\AepBase\Model\ResourceModel\Wishlist as ResourceModel
     */
    private ResourceModel $resourceModel;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

 	/**
     * Plugin constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        ResourceModel $resourceModel,        
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->customerRepository = $customerRepository;
        $this->resourceModel = $resourceModel;
        $this->messageManager = $messageManager;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function updateCustomer($customer, $updatedDate)
    {
    	// set cart modified datetime;
        $customer->getExtensionAttributes()->setAepCartModifiedDatetime($updatedDate);

        try {
            // save the customer
            $this->customerRepository->save($customer);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }
    }
}
