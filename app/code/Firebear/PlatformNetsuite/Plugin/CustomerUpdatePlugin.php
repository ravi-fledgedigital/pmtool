<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Plugin;

use Firebear\PlatformNetsuite\Model\Customer\UpdatePublisher;
use Magento\Customer\Model\ResourceModel\CustomerRepository as CustomerRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class CustomerUpdatePlugin
 * @package Firebear\PlatformNetsuite\Plugin
 */
class CustomerUpdatePlugin
{
    /**
     * @var \Magento\Quote\Model\Product\QuoteItemsCleanerInterface
     */
    private $customerUpdatePublisher;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * CustomerUpdatePlugin constructor.
     * @param UpdatePublisher $customerUpdatePublisher
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        UpdatePublisher $customerUpdatePublisher,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->customerUpdatePublisher = $customerUpdatePublisher;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param CustomerRepository $subject
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function afterSave(
        CustomerRepository $subject,
        \Magento\Customer\Api\Data\CustomerInterface $customer
    ) {
        if ($this->scopeConfig->getValue('firebear_importexport/netsuite/export_customers')) {
            $this->customerUpdatePublisher->execute($customer);
        }
        return $customer;
    }
}
